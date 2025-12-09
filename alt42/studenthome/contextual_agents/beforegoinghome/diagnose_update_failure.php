<?php
/**
 * UPDATE 실패 원인 진단
 * 파일: diagnose_update_failure.php
 * 목적: UPDATE가 왜 실패하는지 상세 분석
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

$result = [
    'test_name' => 'UPDATE Failure Diagnosis',
    'timestamp' => date('Y-m-d H:i:s'),
    'file' => __FILE__
];

try {
    $tableName = 'alt42_goinghome_reports';

    // ============================================================
    // Test 1: 간단한 UPDATE 테스트 (텍스트만)
    // ============================================================
    $result['test_1'] = '간단한 텍스트 UPDATE';

    // INSERT
    $record = new stdClass();
    $record->userid = 1951;
    $record->report_id = 'DIAG_TEST_' . time();
    $record->report_html = '';
    $record->report_data = '';
    $record->report_date = date('Y년 n월 j일');
    $record->timecreated = time();
    $record->timemodified = time();

    $insertId = $DB->insert_record($tableName, $record, true);
    $result['test_1_insert_id'] = $insertId;

    if ($insertId > 0) {
        // UPDATE - 간단한 텍스트
        try {
            $update = new stdClass();
            $update->id = $insertId;
            $update->report_data = 'Simple text without special characters';
            $update->timemodified = time();

            $updateSuccess = $DB->update_record($tableName, $update);
            $result['test_1_simple_update'] = $updateSuccess ? 'SUCCESS' : 'FAILED';

            // 확인
            $check = $DB->get_record($tableName, ['id' => $insertId]);
            $result['test_1_data_saved'] = $check->report_data;

        } catch (Exception $e) {
            $result['test_1_simple_update'] = 'EXCEPTION';
            $result['test_1_error'] = $e->getMessage();
            $result['test_1_error_class'] = get_class($e);
        }

        // 정리
        $DB->delete_records($tableName, ['id' => $insertId]);
    }

    // ============================================================
    // Test 2: JSON UPDATE 테스트
    // ============================================================
    $result['test_2'] = 'JSON 데이터 UPDATE';

    // INSERT
    $record2 = new stdClass();
    $record2->userid = 1951;
    $record2->report_id = 'DIAG_JSON_' . time();
    $record2->report_html = '';
    $record2->report_data = '';
    $record2->report_date = date('Y년 n월 j일');
    $record2->timecreated = time();
    $record2->timemodified = time();

    $insertId2 = $DB->insert_record($tableName, $record2, true);
    $result['test_2_insert_id'] = $insertId2;

    if ($insertId2 > 0) {
        // JSON 데이터 준비
        $jsonData = json_encode([
            'test' => 'value',
            'number' => 123,
            'korean' => '한글 테스트'
        ], JSON_UNESCAPED_UNICODE);

        $result['test_2_json_data'] = $jsonData;
        $result['test_2_json_size'] = strlen($jsonData);

        try {
            $update2 = new stdClass();
            $update2->id = $insertId2;
            $update2->report_data = $jsonData;
            $update2->timemodified = time();

            $updateSuccess2 = $DB->update_record($tableName, $update2);
            $result['test_2_json_update'] = $updateSuccess2 ? 'SUCCESS' : 'FAILED';

            // 확인
            $check2 = $DB->get_record($tableName, ['id' => $insertId2]);
            $result['test_2_data_saved'] = $check2->report_data;

        } catch (dml_exception $e) {
            $result['test_2_json_update'] = 'DML_EXCEPTION';
            $result['test_2_error'] = $e->getMessage();
            $result['test_2_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
            $result['test_2_error_debuginfo'] = isset($e->debuginfo) ? $e->debuginfo : 'no debug';
        } catch (Exception $e) {
            $result['test_2_json_update'] = 'EXCEPTION';
            $result['test_2_error'] = $e->getMessage();
            $result['test_2_error_class'] = get_class($e);
            $result['test_2_error_file'] = $e->getFile();
            $result['test_2_error_line'] = $e->getLine();
        }

        // 정리
        $DB->delete_records($tableName, ['id' => $insertId2]);
    }

    // ============================================================
    // Test 3: 이모지 포함 JSON UPDATE
    // ============================================================
    $result['test_3'] = '이모지 포함 JSON UPDATE';

    // INSERT
    $record3 = new stdClass();
    $record3->userid = 1951;
    $record3->report_id = 'DIAG_EMOJI_' . time();
    $record3->report_html = '';
    $record3->report_data = '';
    $record3->report_date = date('Y년 n월 j일');
    $record3->timecreated = time();
    $record3->timemodified = time();

    $insertId3 = $DB->insert_record($tableName, $record3, true);
    $result['test_3_insert_id'] = $insertId3;

    if ($insertId3 > 0) {
        // 이모지 포함 JSON
        $emojiJsonData = json_encode([
            'message' => '오늘 기분 좋아요 😊',
            'activities' => ['축구 ⚽', '게임 🎮']
        ], JSON_UNESCAPED_UNICODE);

        $result['test_3_json_data'] = $emojiJsonData;
        $result['test_3_json_size'] = strlen($emojiJsonData);

        try {
            $update3 = new stdClass();
            $update3->id = $insertId3;
            $update3->report_data = $emojiJsonData;
            $update3->timemodified = time();

            $updateSuccess3 = $DB->update_record($tableName, $update3);
            $result['test_3_emoji_update'] = $updateSuccess3 ? 'SUCCESS' : 'FAILED';

            // 확인
            $check3 = $DB->get_record($tableName, ['id' => $insertId3]);
            $result['test_3_data_saved'] = $check3->report_data;
            $result['test_3_emoji_preserved'] = (strpos($check3->report_data, '😊') !== false);

        } catch (dml_exception $e) {
            $result['test_3_emoji_update'] = 'DML_EXCEPTION';
            $result['test_3_error'] = $e->getMessage();
            $result['test_3_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
            $result['test_3_error_debuginfo'] = isset($e->debuginfo) ? $e->debuginfo : 'no debug';
        } catch (Exception $e) {
            $result['test_3_emoji_update'] = 'EXCEPTION';
            $result['test_3_error'] = $e->getMessage();
            $result['test_3_error_class'] = get_class($e);
        }

        // 정리
        $DB->delete_records($tableName, ['id' => $insertId3]);
    }

    // ============================================================
    // Test 4: 직접 SQL UPDATE 시도
    // ============================================================
    $result['test_4'] = '직접 SQL UPDATE 시도';

    // INSERT
    $record4 = new stdClass();
    $record4->userid = 1951;
    $record4->report_id = 'DIAG_SQL_' . time();
    $record4->report_html = '';
    $record4->report_data = '';
    $record4->report_date = date('Y년 n월 j일');
    $record4->timecreated = time();
    $record4->timemodified = time();

    $insertId4 = $DB->insert_record($tableName, $record4, true);
    $result['test_4_insert_id'] = $insertId4;

    if ($insertId4 > 0) {
        try {
            $testData = json_encode(['direct' => 'sql test 😊'], JSON_UNESCAPED_UNICODE);

            $sql = "UPDATE {" . $tableName . "}
                    SET report_data = ?, timemodified = ?
                    WHERE id = ?";

            $params = [$testData, time(), $insertId4];

            $DB->execute($sql, $params);
            $result['test_4_direct_sql'] = 'SUCCESS';

            // 확인
            $check4 = $DB->get_record($tableName, ['id' => $insertId4]);
            $result['test_4_data_saved'] = $check4->report_data;

        } catch (Exception $e) {
            $result['test_4_direct_sql'] = 'EXCEPTION';
            $result['test_4_error'] = $e->getMessage();
            $result['test_4_error_class'] = get_class($e);
        }

        // 정리
        $DB->delete_records($tableName, ['id' => $insertId4]);
    }

    // ============================================================
    // 최종 분석
    // ============================================================

    $result['analysis'] = [
        'simple_text_works' => ($result['test_1_simple_update'] ?? '') === 'SUCCESS',
        'json_works' => ($result['test_2_json_update'] ?? '') === 'SUCCESS',
        'emoji_json_works' => ($result['test_3_emoji_update'] ?? '') === 'SUCCESS',
        'direct_sql_works' => ($result['test_4_direct_sql'] ?? '') === 'SUCCESS'
    ];

    // 문제 진단
    if ($result['analysis']['simple_text_works'] &&
        $result['analysis']['json_works'] &&
        $result['analysis']['emoji_json_works']) {
        $result['diagnosis'] = 'UPDATE 기능 정상. test_complete_flow.php의 다른 문제일 가능성';
    } elseif (!$result['analysis']['simple_text_works']) {
        $result['diagnosis'] = 'UPDATE 자체가 실패. 테이블 권한 또는 구조 문제 가능성';
    } elseif ($result['analysis']['simple_text_works'] && !$result['analysis']['json_works']) {
        $result['diagnosis'] = 'JSON 데이터 처리 문제. JSON 형식 또는 특수문자 이슈';
    } elseif ($result['analysis']['json_works'] && !$result['analysis']['emoji_json_works']) {
        $result['diagnosis'] = '이모지 처리 문제. 인코딩 이슈 (utf8mb4가 완전히 적용되지 않음)';
    } else {
        $result['diagnosis'] = '복합적인 문제. 상세 로그 분석 필요';
    }

    $result['success'] = true;

} catch (Exception $e) {
    $result['success'] = false;
    $result['fatal_error'] = $e->getMessage();
    $result['error_class'] = get_class($e);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
