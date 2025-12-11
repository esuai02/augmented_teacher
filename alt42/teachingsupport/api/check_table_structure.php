<?php
/**
 * ktm_quantum_paths 테이블 구조 확인
 */

require_once '/home/moodle/public_html/moodle/config.php';

header('Content-Type: application/json; charset=utf-8');

global $DB, $CFG;

try {
    $tableName = $CFG->prefix . 'ktm_quantum_paths';

    // 테이블 구조 조회
    $sql = "DESCRIBE {$tableName}";
    $columns = $DB->get_records_sql($sql);

    // 현재 데이터 개수
    $countSql = "SELECT COUNT(*) as cnt FROM {$tableName}";
    $count = $DB->get_field_sql($countSql);

    echo json_encode([
        'success' => true,
        'table' => $tableName,
        'columns' => $columns,
        'record_count' => $count
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
