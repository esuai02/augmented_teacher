<?php
/**
 * DB 연결 및 테이블 상태 진단 스크립트
 * 파일: debug_db_connection.php
 * 목적: DB 쓰기 오류의 근본 원인 파악
 */

// Moodle 설정 로드
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

$debug = [];
$debug['script'] = __FILE__;
$debug['line'] = __LINE__;

try {
    // 1. DB 연결 확인
    $debug['db_connected'] = !empty($DB);
    $debug['db_class'] = get_class($DB);

    // 2. 테이블 존재 확인
    $tableName = 'alt42_goinghome_reports';
    $tableExists = $DB->get_manager()->table_exists($tableName);
    $debug['table_exists'] = $tableExists;

    if (!$tableExists) {
        $debug['error'] = '테이블이 존재하지 않습니다';
        $debug['solution'] = 'create_goinghome_reports_table.sql 실행 필요';
        echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. 테이블 구조 확인
    $sql = "DESCRIBE {$tableName}";
    try {
        $columns = $DB->get_records_sql("SHOW COLUMNS FROM {mdl_" . $tableName . "}");
        $debug['table_columns'] = array_keys($columns);
        $debug['column_count'] = count($columns);

        // 각 컬럼의 타입 정보
        $columnDetails = [];
        foreach ($columns as $col) {
            $columnDetails[$col->field] = [
                'type' => $col->type,
                'null' => $col->null,
                'key' => $col->key,
                'default' => $col->default,
                'extra' => $col->extra
            ];
        }
        $debug['column_details'] = $columnDetails;
    } catch (Exception $e) {
        $debug['column_check_error'] = $e->getMessage();
    }

    // 4. 기존 레코드 수 확인
    $count = $DB->count_records($tableName);
    $debug['existing_records'] = $count;

    // 5. 테스트 데이터 삽입 시도
    $testRecord = new stdClass();
    $testRecord->userid = 1951; // 실제 userid
    $testRecord->report_id = 'TEST_' . time();
    $testRecord->report_html = '<div>테스트 HTML</div>';
    $testRecord->report_data = json_encode(['test' => true], JSON_UNESCAPED_UNICODE);
    $testRecord->report_date = date('Y년 n월 j일');
    $testRecord->timecreated = time();
    $testRecord->timemodified = time();

    $debug['test_record'] = [
        'userid' => $testRecord->userid,
        'report_id' => $testRecord->report_id,
        'html_length' => strlen($testRecord->report_html),
        'data_length' => strlen($testRecord->report_data)
    ];

    try {
        $insertId = $DB->insert_record($tableName, $testRecord, true);
        $debug['test_insert_success'] = true;
        $debug['test_insert_id'] = $insertId;

        // 삽입된 데이터 확인
        $inserted = $DB->get_record($tableName, ['id' => $insertId]);
        if ($inserted) {
            $debug['test_record_retrieved'] = true;
            $debug['retrieved_data'] = [
                'id' => $inserted->id,
                'userid' => $inserted->userid,
                'report_id' => $inserted->report_id,
                'html_length' => strlen($inserted->report_html),
                'data_length' => strlen($inserted->report_data)
            ];

            // 테스트 데이터 삭제
            $DB->delete_records($tableName, ['id' => $insertId]);
            $debug['test_record_deleted'] = true;
        }
    } catch (dml_exception $e) {
        $debug['test_insert_error'] = true;
        $debug['error_type'] = 'dml_exception';
        $debug['error_message'] = $e->getMessage();
        $debug['error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
        $debug['error_debuginfo'] = isset($e->debuginfo) ? $e->debuginfo : 'no debug info';
        $debug['error_file'] = $e->getFile();
        $debug['error_line'] = $e->getLine();
    } catch (Exception $e) {
        $debug['test_insert_error'] = true;
        $debug['error_type'] = get_class($e);
        $debug['error_message'] = $e->getMessage();
        $debug['error_file'] = $e->getFile();
        $debug['error_line'] = $e->getLine();
    }

    // 6. DB 권한 확인
    try {
        $grants = $DB->get_records_sql("SHOW GRANTS");
        $debug['has_grant_info'] = !empty($grants);
    } catch (Exception $e) {
        $debug['grant_check_error'] = $e->getMessage();
    }

    // 7. 테이블 크기 및 상태
    try {
        $tableStatus = $DB->get_record_sql("
            SELECT
                table_rows,
                avg_row_length,
                data_length,
                index_length,
                data_free
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = ?
        ", ['mdl_' . $tableName]);

        if ($tableStatus) {
            $debug['table_status'] = [
                'rows' => $tableStatus->table_rows,
                'avg_row_length' => $tableStatus->avg_row_length,
                'data_length' => $tableStatus->data_length,
                'index_length' => $tableStatus->index_length,
                'data_free' => $tableStatus->data_free
            ];
        }
    } catch (Exception $e) {
        $debug['table_status_error'] = $e->getMessage();
    }

    $debug['overall_status'] = $debug['test_insert_success'] ?? false ? 'OK' : 'ERROR';

} catch (Exception $e) {
    $debug['fatal_error'] = $e->getMessage();
    $debug['error_file'] = $e->getFile();
    $debug['error_line'] = $e->getLine();
    $debug['overall_status'] = 'FATAL_ERROR';
}

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
