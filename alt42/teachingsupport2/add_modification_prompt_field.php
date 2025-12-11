<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;
require_login();

try {
    $dbman = $DB->get_manager();
    
    // ktm_teaching_interactions 테이블이 존재하는지 확인
    if ($dbman->table_exists('ktm_teaching_interactions')) {
        // modification_prompt 필드가 존재하는지 확인
        $sql = "SHOW COLUMNS FROM {$CFG->prefix}ktm_teaching_interactions LIKE 'modification_prompt'";
        $exists = $DB->get_records_sql($sql);
        
        if (empty($exists)) {
            // 필드가 없으면 추가
            $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions 
                    ADD COLUMN modification_prompt LONGTEXT DEFAULT NULL 
                    AFTER audio_url";
            $DB->execute($sql);
            echo "modification_prompt 필드가 성공적으로 추가되었습니다.";
        } else {
            echo "modification_prompt 필드가 이미 존재합니다.";
        }
    } else {
        echo "ktm_teaching_interactions 테이블이 존재하지 않습니다.";
    }
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>