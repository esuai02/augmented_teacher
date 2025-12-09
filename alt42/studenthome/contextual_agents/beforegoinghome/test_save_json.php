<?php
/**
 * JSON 데이터 UPDATE 테스트
 * 파일: test_save_json.php
 * 목적: INSERT 후 report_data 필드 UPDATE 성공 여부 확인
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

$result = [
    'test_name' => 'JSON Data UPDATE Test',
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

    // 2. 테스트 JSON 데이터 준비 (3가지 크기)
    $testCases = [
        'small' => [
            'student_id' => 1951,
            'student_name' => '테스트학생',
            'responses' => ['q1' => 'a1', 'q2' => 'a2'],
            'report_id' => 'TEST_JSON_SMALL_' . time()
        ],
        'medium' => [
            'student_id' => 1951,
            'student_name' => '테스트학생',
            'responses' => array_fill(0, 100, '중간 크기 응답 데이터'),
            'report_id' => 'TEST_JSON_MEDIUM_' . time(),
            'extra_data' => str_repeat('데이터', 1000)
        ],
        'large' => [
            'student_id' => 1951,
            'student_name' => '테스트학생',
            'responses' => array_fill(0, 1000, '큰 응답 데이터'),
            'report_id' => 'TEST_JSON_LARGE_' . time(),
            'extra_data' => str_repeat('대용량데이터', 10000)
        ]
    ];

    $result['test_cases'] = [];

    foreach ($testCases as $testName => $testData) {
        $caseResult = ['name' => $testName];

        try {
            // 3. 기본 레코드 INSERT
            $record = new stdClass();
            $record->userid = 1951;
            $record->report_id = $testData['report_id'];
            $record->report_html = '';
            $record->report_data = '';
            $record->report_date = date('Y년 n월 j일');
            $record->timecreated = time();
            $record->timemodified = time();

            $insertId = $DB->insert_record($tableName, $record, true);
            $caseResult['insert_success'] = ($insertId > 0);
            $caseResult['insert_id'] = $insertId;

            if ($insertId > 0) {
                // 4. JSON 데이터 준비
                $jsonData = json_encode($testData, JSON_UNESCAPED_UNICODE);
                $jsonSize = strlen($jsonData);
                $caseResult['json_size'] = $jsonSize;
                $caseResult['json_size_mb'] = round($jsonSize / (1024 * 1024), 2);

                // 5. 크기 검증
                if ($jsonSize > 16 * 1024 * 1024) {
                    $caseResult['size_check'] = 'TOO_LARGE';
                    $caseResult['error'] = "JSON 데이터가 너무 큽니다: {$jsonSize} bytes";
                    $caseResult['update_attempted'] = false;
                } else {
                    $caseResult['size_check'] = 'OK';

                    // 6. UPDATE 시도
                    $updateRecord = new stdClass();
                    $updateRecord->id = $insertId;
                    $updateRecord->report_data = $jsonData;
                    $updateRecord->timemodified = time();

                    $updateSuccess = $DB->update_record($tableName, $updateRecord);
                    $caseResult['update_success'] = $updateSuccess;
                    $caseResult['update_attempted'] = true;

                    if ($updateSuccess) {
                        // 7. 업데이트된 데이터 확인
                        $retrieved = $DB->get_record($tableName, ['id' => $insertId]);

                        if ($retrieved && !empty($retrieved->report_data)) {
                            $retrievedData = json_decode($retrieved->report_data, true);
                            $caseResult['retrieve_success'] = true;
                            $caseResult['data_integrity'] = ($retrievedData['report_id'] === $testData['report_id']);
                            $caseResult['retrieved_size'] = strlen($retrieved->report_data);
                        } else {
                            $caseResult['retrieve_success'] = false;
                            $caseResult['error'] = 'UPDATE된 데이터를 조회할 수 없습니다';
                        }
                    }
                }

                // 8. 정리
                $DB->delete_records($tableName, ['id' => $insertId]);
                $caseResult['cleanup_success'] = true;

                $caseResult['overall_status'] = ($caseResult['update_success'] ?? false) ? 'SUCCESS' : 'FAIL';
            } else {
                $caseResult['overall_status'] = 'INSERT_FAIL';
                $caseResult['error'] = 'INSERT 실패';
            }

        } catch (dml_exception $e) {
            $caseResult['error_type'] = 'dml_exception';
            $caseResult['error_message'] = $e->getMessage();
            $caseResult['error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
            $caseResult['overall_status'] = 'DML_ERROR';

        } catch (Exception $e) {
            $caseResult['error_type'] = get_class($e);
            $caseResult['error_message'] = $e->getMessage();
            $caseResult['overall_status'] = 'EXCEPTION';
        }

        $result['test_cases'][] = $caseResult;
    }

    // 전체 성공 여부
    $allSuccess = true;
    foreach ($result['test_cases'] as $case) {
        if ($case['overall_status'] !== 'SUCCESS') {
            $allSuccess = false;
            break;
        }
    }

    $result['success'] = $allSuccess;
    $result['overall_status'] = $allSuccess ? 'ALL_SUCCESS' : 'SOME_FAILED';

} catch (Exception $e) {
    $result['success'] = false;
    $result['error_type'] = get_class($e);
    $result['error_message'] = $e->getMessage();
    $result['error_file'] = $e->getFile();
    $result['error_line'] = $e->getLine();
    $result['overall_status'] = 'FATAL_ERROR';
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
