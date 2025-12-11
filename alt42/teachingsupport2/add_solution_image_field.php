<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 체크
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Add Solution Image Field</title></head><body>";
echo "<h2>Adding solution_image field to ktm_teaching_interactions table...</h2>";

try {
    $dbman = $DB->get_manager();
    
    // ktm_teaching_interactions 테이블 가져오기
    $table = new xmldb_table('ktm_teaching_interactions');
    
    if ($dbman->table_exists($table)) {
        // solution_image 필드 존재 확인
        $field = new xmldb_field('solution_image', XMLDB_TYPE_TEXT, null, null, null, null, null);
        
        if (!$dbman->field_exists($table, $field)) {
            // solution_image 필드 추가
            $dbman->add_field($table, $field);
            echo "<p style='color: green;'>✓ Field 'solution_image' added to 'ktm_teaching_interactions' table successfully.</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Field 'solution_image' already exists in 'ktm_teaching_interactions' table.</p>";
        }
        
        // 기존 레코드 확인
        $count = $DB->count_records('ktm_teaching_interactions');
        echo "<p style='color: blue;'>ℹ Current records in ktm_teaching_interactions: {$count}</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Table 'ktm_teaching_interactions' does not exist.</p>";
    }
    
    echo "<h3>✓ Database update completed successfully!</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>