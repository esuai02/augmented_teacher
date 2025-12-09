<?php
/**
 * Confidence Booster 플러그인 업그레이드 스크립트
 * 
 * @package    local_confidence_booster
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * 플러그인 업그레이드 함수
 * 
 * @param int $oldversion 이전 버전
 * @return bool 성공 여부
 */
function xmldb_local_confidence_booster_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    
    // 버전 2024011501로 업그레이드
    if ($oldversion < 2024011501) {
        
        // Define table confidence_badges to be created
        $table = new xmldb_table('confidence_badges');
        
        // Adding fields to table
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('badge_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('badge_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('badge_icon', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('earned_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        
        // Adding keys to table
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        
        // Adding indexes to table
        $table->add_index('userid_badge', XMLDB_INDEX_NOTUNIQUE, array('userid', 'badge_type'));
        
        // Create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2024011501, 'local', 'confidence_booster');
    }
    
    // 버전 2024011502로 업그레이드
    if ($oldversion < 2024011502) {
        
        // Add new field to confidence_notes table
        $table = new xmldb_table('confidence_notes');
        $field = new xmldb_field('is_pinned', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'quality_score');
        
        // Add field
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2024011502, 'local', 'confidence_booster');
    }
    
    // 버전 2024011503로 업그레이드
    if ($oldversion < 2024011503) {
        
        // Define table confidence_study_sessions to be created
        $table = new xmldb_table('confidence_study_sessions');
        
        // Adding fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('session_date', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('start_time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('end_time', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('duration_minutes', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('summaries_created', XMLDB_TYPE_INTEGER, '5', null, null, null, '0');
        $table->add_field('errors_classified', XMLDB_TYPE_INTEGER, '5', null, null, null, '0');
        $table->add_field('focus_score', XMLDB_TYPE_NUMBER, '3, 2', null, null, null, null);
        
        // Adding keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        
        // Adding indexes
        $table->add_index('userid_date', XMLDB_INDEX_NOTUNIQUE, array('userid', 'session_date'));
        
        // Create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2024011503, 'local', 'confidence_booster');
    }
    
    // 버전 2024011504로 업그레이드 - 인덱스 최적화
    if ($oldversion < 2024011504) {
        
        // Add composite index for performance
        $table = new xmldb_table('confidence_notes');
        $index = new xmldb_index('userid_created_composite', XMLDB_INDEX_NOTUNIQUE, array('userid', 'timecreated'));
        
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        // Add index for error resolution tracking
        $table = new xmldb_table('confidence_errors');
        $index = new xmldb_index('userid_resolved_composite', XMLDB_INDEX_NOTUNIQUE, array('userid', 'resolved', 'error_type'));
        
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        // Savepoint reached
        upgrade_plugin_savepoint(true, 2024011504, 'local', 'confidence_booster');
    }
    
    return true;
}

/**
 * 플러그인 제거 시 실행되는 함수
 * 
 * @return bool 성공 여부
 */
function xmldb_local_confidence_booster_uninstall() {
    global $DB;
    $dbman = $DB->get_manager();
    
    // 테이블 삭제 순서 (외래키 의존성 고려)
    $tables = [
        'confidence_feedback',
        'confidence_metrics',
        'confidence_challenges',
        'confidence_errors',
        'confidence_notes',
        'confidence_badges',
        'confidence_study_sessions'
    ];
    
    foreach ($tables as $tablename) {
        $table = new xmldb_table($tablename);
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
    }
    
    return true;
}
?>