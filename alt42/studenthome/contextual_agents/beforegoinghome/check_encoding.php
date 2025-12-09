<?php
/**
 * 컬럼 인코딩 간단 체크
 * 파일: check_encoding.php
 * 목적: 현재 테이블 컬럼의 CHARACTER SET 확인
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB;

header('Content-Type: application/json; charset=utf-8');

$result = $DB->get_records_sql("
    SELECT
        COLUMN_NAME,
        CHARACTER_SET_NAME,
        COLLATION_NAME,
        COLUMN_TYPE,
        COLUMN_COMMENT
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mdl_alt42_goinghome_reports'
      AND COLUMN_NAME IN ('report_html', 'report_data')
    ORDER BY COLUMN_NAME
");

$output = [
    'timestamp' => date('Y-m-d H:i:s'),
    'columns' => []
];

foreach ($result as $col) {
    $output['columns'][$col->column_name] = [
        'charset' => $col->character_set_name,
        'collation' => $col->collation_name,
        'type' => $col->column_type,
        'comment' => $col->column_comment
    ];
}

// 전체 컬럼이 utf8mb4인지 확인
$allUtf8mb4 = true;
foreach ($output['columns'] as $colName => $colInfo) {
    if ($colInfo['charset'] !== 'utf8mb4') {
        $allUtf8mb4 = false;
        break;
    }
}

$output['all_utf8mb4'] = $allUtf8mb4;
$output['status'] = $allUtf8mb4 ? 'READY' : 'MIGRATION_NEEDED';

if ($allUtf8mb4) {
    $output['message'] = '✅ UTF-8mb4 변환 완료! 이모지 저장 가능합니다.';
    $output['next_step'] = 'test_complete_flow.php 실행하여 전체 플로우 테스트';
} else {
    $output['message'] = '❌ 아직 utf8 입니다. migrate_to_utf8mb4.php를 실행하세요.';
    $output['next_step'] = 'migrate_to_utf8mb4.php 실행 필요';
}

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
