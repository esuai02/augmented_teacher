<?php
/**
 * Moodle DB Manager를 사용한 테이블 생성
 */

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $CFG;
require_login();

// 관리자 권한 확인
if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

require_once($CFG->libdir.'/ddllib.php');

echo "<h2>Moodle DB Manager를 사용한 테이블 생성</h2>";

try {
    $dbman = $DB->get_manager();
    
    // 테이블 정의
    $table = new xmldb_table('alt42i_user_pattern_progress');
    
    // 필드 추가
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('pattern_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('is_collected', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
    $table->add_field('mastery_level', XMLDB_TYPE_INTEGER, '3', null, null, null, '0');
    $table->add_field('practice_count', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
    $table->add_field('last_practice_at', XMLDB_TYPE_DATETIME, null, null, null, null, null);
    $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('created_at', XMLDB_TYPE_DATETIME, null, null, XMLDB_NOTNULL, null, null);
    $table->add_field('updated_at', XMLDB_TYPE_DATETIME, null, null, XMLDB_NOTNULL, null, null);
    
    // 키 추가
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    
    // 인덱스 추가
    $table->add_index('idx_user_pattern', XMLDB_INDEX_UNIQUE, array('user_id', 'pattern_id'));
    $table->add_index('idx_pattern', XMLDB_INDEX_NOTUNIQUE, array('pattern_id'));
    
    // 테이블이 이미 존재하는지 확인
    if ($dbman->table_exists($table)) {
        echo "<p>테이블이 이미 존재합니다. 삭제 후 재생성합니다...</p>";
        $dbman->drop_table($table);
    }
    
    // 테이블 생성
    $dbman->create_table($table);
    echo "<p style='color: green;'>✅ 테이블이 성공적으로 생성되었습니다!</p>";
    
    // 다른 테이블들도 생성
    
    // 2. 연습 로그 테이블
    $table2 = new xmldb_table('alt42i_pattern_practice_logs');
    $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table2->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table2->add_field('pattern_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table2->add_field('practice_type', XMLDB_TYPE_CHAR, '50', null, null, null, 'self');
    $table2->add_field('duration_seconds', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
    $table2->add_field('feedback', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table2->add_field('is_completed', XMLDB_TYPE_INTEGER, '1', null, null, null, '1');
    $table2->add_field('created_at', XMLDB_TYPE_DATETIME, null, null, XMLDB_NOTNULL, null, null);
    
    $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table2->add_index('idx_user_pattern_date', XMLDB_INDEX_NOTUNIQUE, array('user_id', 'pattern_id'));
    
    if (!$dbman->table_exists($table2)) {
        $dbman->create_table($table2);
        echo "<p style='color: green;'>✅ 연습 로그 테이블이 생성되었습니다.</p>";
    }
    
    // 3. 오디오 재생 로그 테이블
    $table3 = new xmldb_table('alt42i_audio_play_logs');
    $table3->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table3->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table3->add_field('pattern_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table3->add_field('played_at', XMLDB_TYPE_DATETIME, null, null, XMLDB_NOTNULL, null, null);
    
    $table3->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table3->add_index('idx_user_pattern_play', XMLDB_INDEX_NOTUNIQUE, array('user_id', 'pattern_id'));
    
    if (!$dbman->table_exists($table3)) {
        $dbman->create_table($table3);
        echo "<p style='color: green;'>✅ 오디오 재생 로그 테이블이 생성되었습니다.</p>";
    }
    
    // 샘플 데이터 추가
    echo "<h3>샘플 데이터 추가</h3>";
    
    for ($i = 1; $i <= 10; $i++) {
        $existing = $DB->get_record('alt42i_user_pattern_progress', [
            'user_id' => $USER->id,
            'pattern_id' => $i
        ]);
        
        if (!$existing) {
            $progress = new stdClass();
            $progress->user_id = $USER->id;
            $progress->pattern_id = $i;
            $progress->is_collected = ($i <= 5) ? 1 : 0;
            $progress->mastery_level = ($i <= 5) ? rand(30, 80) : 0;
            $progress->practice_count = ($i <= 5) ? rand(1, 5) : 0;
            $progress->created_at = date('Y-m-d H:i:s');
            $progress->updated_at = date('Y-m-d H:i:s');
            
            $DB->insert_record('alt42i_user_pattern_progress', $progress);
        }
    }
    echo "<p style='color: green;'>샘플 데이터가 추가되었습니다.</p>";
    
    // 생성 확인
    $count = $DB->count_records('alt42i_user_pattern_progress');
    echo "<p>현재 진행 상황 레코드 수: $count</p>";
    
    echo "<h3>✅ 완료!</h3>";
    echo "<p><a href='show_math_patterns.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>수학 인지관성 도감 보기</a></p>";
    echo "<p><a href='index.php' style='padding: 10px 20px; background: #764ba2; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>메인 페이지로</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 오류 발생: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}