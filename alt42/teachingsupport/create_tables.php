<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 체크
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Create KTM Tables</title></head><body>";
echo "<h2>Creating KTM Teaching Tables...</h2>";

try {
    $dbman = $DB->get_manager();
    
    // mdl_ktm_teaching_interactions 테이블 생성
    $table1 = new xmldb_table('ktm_teaching_interactions');
    
    if (!$dbman->table_exists($table1)) {
        // 필드 추가
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table1->add_field('problem_type', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table1->add_field('problem_image', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('problem_text', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('solution_text', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('narration_text', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('audio_url', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('status', XMLDB_TYPE_CHAR, '20', null, null, null, 'pending');
        $table1->add_field('score', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table1->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        // 키 추가
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        // 인덱스 추가
        $table1->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table1->add_index('teacherid_idx', XMLDB_INDEX_NOTUNIQUE, array('teacherid'));
        $table1->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, array('status'));
        $table1->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, array('timecreated'));
        
        // 테이블 생성
        $dbman->create_table($table1);
        echo "<p style='color: green;'>✓ Table 'mdl_ktm_teaching_interactions' created successfully.</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Table 'mdl_ktm_teaching_interactions' already exists.</p>";
    }
    
    // mdl_ktm_teaching_events 테이블 생성
    $table2 = new xmldb_table('ktm_teaching_events');
    
    if (!$dbman->table_exists($table2)) {
        // 필드 추가
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('interactionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table2->add_field('event_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('event_description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table2->add_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table2->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        // 키 추가
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        // 인덱스 추가
        $table2->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table2->add_index('interaction_idx', XMLDB_INDEX_NOTUNIQUE, array('interactionid'));
        $table2->add_index('type_idx', XMLDB_INDEX_NOTUNIQUE, array('event_type'));
        $table2->add_index('time_idx', XMLDB_INDEX_NOTUNIQUE, array('timecreated'));
        
        // 테이블 생성
        $dbman->create_table($table2);
        echo "<p style='color: green;'>✓ Table 'mdl_ktm_teaching_events' created successfully.</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Table 'mdl_ktm_teaching_events' already exists.</p>";
    }
    
    echo "<h3>✓ All tables checked/created successfully!</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>