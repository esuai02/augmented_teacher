<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $CFG;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<h2>데이터베이스 필드 수정 스크립트</h2>";

try {
    $dbman = $DB->get_manager();
    
    // 1. modification_prompt 필드 추가
    echo "<h3>1. modification_prompt 필드 확인 및 추가</h3>";
    if ($dbman->table_exists('ktm_teaching_interactions')) {
        $sql = "SHOW COLUMNS FROM {$CFG->prefix}ktm_teaching_interactions LIKE 'modification_prompt'";
        $exists = $DB->get_records_sql($sql);
        
        if (empty($exists)) {
            $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions 
                    ADD COLUMN modification_prompt LONGTEXT DEFAULT NULL 
                    AFTER audio_url";
            $DB->execute($sql);
            echo "✅ modification_prompt 필드가 추가되었습니다.<br>";
        } else {
            echo "ℹ️ modification_prompt 필드가 이미 존재합니다.<br>";
        }
    }
    
    // 2. webvtt_data 필드 추가
    echo "<h3>2. webvtt_data 필드 확인 및 추가</h3>";
    if ($dbman->table_exists('ktm_teaching_interactions')) {
        $sql = "SHOW COLUMNS FROM {$CFG->prefix}ktm_teaching_interactions LIKE 'webvtt_data'";
        $exists = $DB->get_records_sql($sql);
        
        if (empty($exists)) {
            $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions 
                    ADD COLUMN webvtt_data LONGTEXT DEFAULT NULL 
                    AFTER narration_text";
            $DB->execute($sql);
            echo "✅ webvtt_data 필드가 추가되었습니다.<br>";
        } else {
            echo "ℹ️ webvtt_data 필드가 이미 존재합니다.<br>";
        }
    }
    
    // 3. 테이블 구조 확인
    echo "<h3>3. ktm_teaching_interactions 테이블 현재 구조</h3>";
    $sql = "SHOW COLUMNS FROM {$CFG->prefix}ktm_teaching_interactions";
    $columns = $DB->get_records_sql($sql);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>필드명</th><th>타입</th><th>Null</th><th>기본값</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column->field}</td>";
        echo "<td>{$column->type}</td>";
        echo "<td>{$column->null}</td>";
        echo "<td>{$column->default}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ 데이터베이스 수정 완료</h3>";
    echo "<p>이제 '다시 생성하기' 기능이 정상적으로 작동해야 합니다.</p>";
    
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