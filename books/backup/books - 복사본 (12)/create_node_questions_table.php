<?php
/**
 * 노드별 질문/답변 테이블 생성 스크립트
 *
 * @author AI Learning System
 * @created 2025-01-26
 * @file books/create_node_questions_table.php
 */

require_once('/home/moodle/public_html/moodle/config.php');
global $DB;

error_log('[create_node_questions_table.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Starting table creation...');

try {
    $dbman = $DB->get_manager();

    // 1. 노드별 질문 테이블
    $table1 = new xmldb_table('abrainalignment_node_questions');

    if (!$dbman->table_exists($table1)) {
        // 필드 정의
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('contentsid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('contentstype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('nstep', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '1');
        $table1->add_field('node_index', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('node_content', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('node_type', XMLDB_TYPE_CHAR, '50', null, null, null, 'premise');
        $table1->add_field('questions_json', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // 키 정의
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // 인덱스 정의
        $table1->add_index('idx_content_node', XMLDB_INDEX_NOTUNIQUE, ['contentsid', 'contentstype', 'nstep', 'node_index']);

        // 테이블 생성
        $dbman->create_table($table1);

        error_log('[create_node_questions_table.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Table abrainalignment_node_questions created successfully');
    } else {
        error_log('[create_node_questions_table.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Table abrainalignment_node_questions already exists');
    }

    // 2. 노드별 답변 테이블
    $table2 = new xmldb_table('abrainalignment_node_answers');

    if (!$dbman->table_exists($table2)) {
        // 필드 정의
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('contentsid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('contentstype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('nstep', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '1');
        $table2->add_field('node_index', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('question_index', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('question', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table2->add_field('answer', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table2->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // 키 정의
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // 인덱스 정의
        $table2->add_index('idx_answer_lookup', XMLDB_INDEX_NOTUNIQUE, ['contentsid', 'contentstype', 'nstep', 'node_index', 'question_index']);

        // 테이블 생성
        $dbman->create_table($table2);

        error_log('[create_node_questions_table.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Table abrainalignment_node_answers created successfully');
    } else {
        error_log('[create_node_questions_table.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Table abrainalignment_node_answers already exists');
    }

    echo "✅ 테이블 생성 완료\n\n";
    echo "생성된 테이블:\n";
    echo "1. mdl_abrainalignment_node_questions (노드별 질문)\n";
    echo "2. mdl_abrainalignment_node_answers (질문별 답변)\n";

} catch (Exception $e) {
    error_log('[create_node_questions_table.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Error: ' . $e->getMessage());
    echo "❌ 오류 발생: " . $e->getMessage() . "\n";
    echo "File: " . basename(__FILE__) . ", Line: " . __LINE__ . "\n";
}
