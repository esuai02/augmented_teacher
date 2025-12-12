<?php
/**
 * Agent 01 DB 스키마 동기화 실행 스크립트
 * 1_db_schema_sync.sql의 내용을 안전하게 실행합니다.
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;
require_login();

// 관리자 권한 체크 (필요시 주석 해제)
// require_capability('moodle/site:config', context_system::instance());

echo "<h1>Agent 01 DB Schema Sync</h1>";
echo "<pre>";

$table_name = 'alt42o_onboarding';
$manager = $DB->get_manager();
$xmldb_table = new xmldb_table($table_name);

// 1. 테이블 존재 여부 확인 및 생성
if (!$manager->table_exists($xmldb_table)) {
    echo "Creating table {$table_name}...\n";
    
    $xmldb_table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $xmldb_table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $xmldb_table->add_field('school', XMLDB_TYPE_CHAR, '255', null, null, null, null);
    $xmldb_table->add_field('birth_year', XMLDB_TYPE_INTEGER, '4', null, null, null, null);
    $xmldb_table->add_field('course_level', XMLDB_TYPE_CHAR, '10', null, null, null, null);
    $xmldb_table->add_field('grade_detail', XMLDB_TYPE_CHAR, '10', null, null, null, null);
    $xmldb_table->add_field('concept_level', XMLDB_TYPE_CHAR, '10', null, null, null, null);
    $xmldb_table->add_field('concept_progress', XMLDB_TYPE_CHAR, '50', null, null, null, null); // INT -> CHAR 변경 (1-2 같은 형식 지원)
    $xmldb_table->add_field('advanced_level', XMLDB_TYPE_CHAR, '10', null, null, null, null);
    $xmldb_table->add_field('advanced_progress', XMLDB_TYPE_CHAR, '50', null, null, null, null); // INT -> CHAR 변경
    $xmldb_table->add_field('learning_notes', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $xmldb_table->add_field('problem_preference', XMLDB_TYPE_CHAR, '50', null, null, null, null);
    $xmldb_table->add_field('exam_style', XMLDB_TYPE_CHAR, '50', null, null, null, null);
    $xmldb_table->add_field('math_confidence', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
    $xmldb_table->add_field('parent_style', XMLDB_TYPE_CHAR, '50', null, null, null, null);
    $xmldb_table->add_field('stress_level', XMLDB_TYPE_CHAR, '20', null, null, null, null);
    $xmldb_table->add_field('feedback_preference', XMLDB_TYPE_CHAR, '50', null, null, null, null);
    $xmldb_table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $xmldb_table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

    $xmldb_table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $xmldb_table->add_key('userid_unique', XMLDB_KEY_UNIQUE, ['userid']);

    try {
        $manager->create_table($xmldb_table);
        echo "Table {$table_name} created successfully.\n";
    } catch (Exception $e) {
        echo "Error creating table: " . $e->getMessage() . "\n";
    }
} else {
    echo "Table {$table_name} already exists.\n";
}

// 2. 컬럼 추가 (rules.yaml 요구사항)
$columns_to_add = [
    'math_learning_style' => ['type' => XMLDB_TYPE_CHAR, 'precision' => '50', 'default' => null],
    'academy_name' => ['type' => XMLDB_TYPE_CHAR, 'precision' => '255', 'default' => null],
    'academy_grade' => ['type' => XMLDB_TYPE_CHAR, 'precision' => '100', 'default' => null],
    'academy_schedule' => ['type' => XMLDB_TYPE_CHAR, 'precision' => '255', 'default' => null],
    'math_recent_score' => ['type' => XMLDB_TYPE_CHAR, 'precision' => '100', 'default' => null],
    'math_weak_units' => ['type' => XMLDB_TYPE_TEXT, 'precision' => null, 'default' => null],
    'textbooks' => ['type' => XMLDB_TYPE_TEXT, 'precision' => null, 'default' => null],
    'math_unit_mastery' => ['type' => XMLDB_TYPE_TEXT, 'precision' => null, 'default' => null], // JSON -> TEXT (MySQL 5.7 호환성)
];

// 실제 DB 컬럼 확인
$columns = $DB->get_columns($table_name);

foreach ($columns_to_add as $field_name => $info) {
    if (!isset($columns[$field_name])) {
        echo "Adding column {$field_name}...\n";
        $field = new xmldb_field($field_name, $info['type'], $info['precision'], null, null, null, null, $info['default']);
        try {
            $manager->add_field($xmldb_table, $field);
            echo "Column {$field_name} added.\n";
        } catch (Exception $e) {
            echo "Error adding column {$field_name}: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Column {$field_name} already exists.\n";
    }
}

echo "Done.\n";
echo "</pre>";
?>
