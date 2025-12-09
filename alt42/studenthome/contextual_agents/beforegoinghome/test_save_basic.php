<?php
/**
 * 기본 레코드 INSERT 테스트
 * 파일: test_save_basic.php
 * 목적: 최소 데이터로 INSERT 성공 여부 확인
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

$result = [
    'test_name' => 'Basic Record INSERT Test',
    'timestamp' => date('Y-m-d H:i:s'),
    'file' => __FILE__,
    'line' => __LINE__
];

try {
    // 1. 테이블 존재 확인
    $tableName = 'alt42_goinghome_reports';
    $tableExists = $DB->get_manager()->table_exists($tableName);

    if (!$tableExists) {
        $result['error'] = '테이블이 존재하지 않습니다';
        $result['success'] = false;
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result['table_exists'] = true;

    // 2. 기본 레코드만 INSERT
    $record = new stdClass();
    $record->userid = 1951; // 테스트 사용자
    $record->report_id = 'TEST_BASIC_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    $record->report_html = ''; // 빈 값
    $record->report_data = ''; // 빈 값
    $record->report_date = date('Y년 n월 j일');
    $record->timecreated = time();
    $record->timemodified = time();

    $result['record_data'] = [
        'userid' => $record->userid,
        'report_id' => $record->report_id,
        'report_date' => $record->report_date,
        'html_length' => strlen($record->report_html),
        'data_length' => strlen($record->report_data)
    ];

    // 3. INSERT 시도
    $insertId = $DB->insert_record($tableName, $record, true);

    if ($insertId && $insertId > 0) {
        $result['insert_success'] = true;
        $result['insert_id'] = $insertId;

        // 4. 삽입된 데이터 확인
        $retrieved = $DB->get_record($tableName, ['id' => $insertId]);

        if ($retrieved) {
            $result['retrieve_success'] = true;
            $result['retrieved_data'] = [
                'id' => $retrieved->id,
                'userid' => $retrieved->userid,
                'report_id' => $retrieved->report_id,
                'report_date' => $retrieved->report_date
            ];

            // 5. 테스트 데이터 삭제
            $deleted = $DB->delete_records($tableName, ['id' => $insertId]);
            $result['cleanup_success'] = $deleted;

            $result['overall_status'] = 'SUCCESS';
            $result['success'] = true;
        } else {
            $result['retrieve_success'] = false;
            $result['error'] = '삽입된 데이터를 조회할 수 없습니다';
            $result['overall_status'] = 'PARTIAL_FAIL';
            $result['success'] = false;
        }
    } else {
        $result['insert_success'] = false;
        $result['insert_id'] = $insertId;
        $result['error'] = 'insert_record가 0 또는 false를 반환했습니다';
        $result['overall_status'] = 'FAIL';
        $result['success'] = false;
    }

} catch (dml_exception $e) {
    $result['success'] = false;
    $result['error_type'] = 'dml_exception';
    $result['error_message'] = $e->getMessage();
    $result['error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
    $result['error_debuginfo'] = isset($e->debuginfo) ? $e->debuginfo : 'no debug info';
    $result['error_file'] = $e->getFile();
    $result['error_line'] = $e->getLine();
    $result['overall_status'] = 'DML_ERROR';

} catch (Exception $e) {
    $result['success'] = false;
    $result['error_type'] = get_class($e);
    $result['error_message'] = $e->getMessage();
    $result['error_file'] = $e->getFile();
    $result['error_line'] = $e->getLine();
    $result['overall_status'] = 'EXCEPTION';
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
