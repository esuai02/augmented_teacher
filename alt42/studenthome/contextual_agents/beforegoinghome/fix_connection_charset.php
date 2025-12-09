<?php
/**
 * Moodle 연결 Charset 강제 설정
 * 파일: fix_connection_charset.php
 * 목적: Moodle 데이터베이스 연결을 utf8mb4로 강제 설정
 *
 * 문제: 컬럼은 utf8mb4인데 연결은 utf8일 수 있음
 * 해결: SET NAMES utf8mb4로 연결 charset 변경
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB;

header('Content-Type: application/json; charset=utf-8');

$result = [
    'test_name' => 'Connection Charset Fix Test',
    'timestamp' => date('Y-m-d H:i:s'),
    'file' => __FILE__
];

try {
    $tableName = 'alt42_goinghome_reports';

    // ============================================================
    // Step 1: 현재 연결 charset 확인
    // ============================================================
    $result['step_1'] = '현재 연결 charset 확인';

    $connectionVars = $DB->get_records_sql("
        SHOW VARIABLES WHERE Variable_name IN (
            'character_set_client',
            'character_set_connection',
            'character_set_results',
            'character_set_database',
            'collation_connection',
            'collation_database'
        )
    ");

    $result['current_connection'] = [];
    foreach ($connectionVars as $var) {
        $result['current_connection'][$var->variable_name] = $var->value;
    }

    // ============================================================
    // Step 2: utf8mb4로 연결 charset 변경
    // ============================================================
    $result['step_2'] = 'utf8mb4로 연결 charset 변경';

    try {
        $DB->execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        $result['set_names_success'] = true;
    } catch (Exception $e) {
        $result['set_names_success'] = false;
        $result['set_names_error'] = $e->getMessage();
    }

    // ============================================================
    // Step 3: 변경 후 연결 charset 재확인
    // ============================================================
    $result['step_3'] = '변경 후 연결 charset 재확인';

    $newConnectionVars = $DB->get_records_sql("
        SHOW VARIABLES WHERE Variable_name IN (
            'character_set_client',
            'character_set_connection',
            'character_set_results'
        )
    ");

    $result['new_connection'] = [];
    foreach ($newConnectionVars as $var) {
        $result['new_connection'][$var->variable_name] = $var->value;
    }

    // ============================================================
    // Step 4: 이모지 UPDATE 테스트
    // ============================================================
    $result['step_4'] = '이모지 UPDATE 테스트';

    // INSERT
    $record = new stdClass();
    $record->userid = 1951;
    $record->report_id = 'CHARSET_FIX_' . time();
    $record->report_html = '';
    $record->report_data = '';
    $record->report_date = date('Y년 n월 j일');
    $record->timecreated = time();
    $record->timemodified = time();

    $insertId = $DB->insert_record($tableName, $record, true);
    $result['test_insert_id'] = $insertId;

    if ($insertId > 0) {
        // 이모지 포함 JSON 데이터
        $testData = json_encode([
            'test' => 'charset fix test',
            'emoji' => '테스트 😊😄⚽🎮',
            'korean' => '한글도 잘 저장되나요?'
        ], JSON_UNESCAPED_UNICODE);

        $result['test_data'] = $testData;
        $result['test_data_size'] = strlen($testData);

        try {
            // UPDATE with emoji
            $update = new stdClass();
            $update->id = $insertId;
            $update->report_data = $testData;
            $update->timemodified = time();

            $updateSuccess = $DB->update_record($tableName, $update);

            if ($updateSuccess) {
                $result['update_success'] = true;

                // 확인
                $check = $DB->get_record($tableName, ['id' => $insertId]);
                $result['data_saved'] = $check->report_data;
                $result['emoji_preserved'] = (strpos($check->report_data, '😊') !== false);

            } else {
                $result['update_success'] = false;
                $result['update_error'] = 'update_record returned false';
            }

        } catch (dml_exception $e) {
            $result['update_success'] = false;
            $result['update_dml_exception'] = $e->getMessage();
            $result['update_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
        } catch (Exception $e) {
            $result['update_success'] = false;
            $result['update_exception'] = $e->getMessage();
            $result['update_exception_class'] = get_class($e);
        }

        // 정리
        $DB->delete_records($tableName, ['id' => $insertId]);
    }

    // ============================================================
    // Step 5: 최종 분석
    // ============================================================

    $charsetWasUtf8 = (
        ($result['current_connection']['character_set_client'] ?? '') === 'utf8' ||
        ($result['current_connection']['character_set_connection'] ?? '') === 'utf8'
    );

    $charsetIsNowUtf8mb4 = (
        ($result['new_connection']['character_set_client'] ?? '') === 'utf8mb4' &&
        ($result['new_connection']['character_set_connection'] ?? '') === 'utf8mb4'
    );

    $updateWorked = ($result['update_success'] ?? false);
    $emojiPreserved = ($result['emoji_preserved'] ?? false);

    $result['diagnosis'] = [
        'charset_was_utf8' => $charsetWasUtf8,
        'charset_fixed_to_utf8mb4' => $charsetIsNowUtf8mb4,
        'update_worked_after_fix' => $updateWorked,
        'emoji_preserved_after_fix' => $emojiPreserved
    ];

    if ($charsetWasUtf8 && $charsetIsNowUtf8mb4 && $updateWorked && $emojiPreserved) {
        $result['conclusion'] = [
            'message' => '✅ 문제 발견! Moodle 연결이 utf8이었고, utf8mb4로 변경 후 정상 작동',
            'root_cause' => '테이블 컬럼은 utf8mb4지만 Moodle 연결은 utf8이었음',
            'solution' => 'index.php 상단에 "SET NAMES utf8mb4" 추가 필요',
            'code_to_add' => '$DB->execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");'
        ];
    } elseif (!$charsetWasUtf8) {
        $result['conclusion'] = [
            'message' => '⚠️ 연결은 이미 utf8mb4였음. 다른 원인일 가능성',
            'next_step' => 'diagnose_update_failure.php 실행 필요'
        ];
    } else {
        $result['conclusion'] = [
            'message' => '❌ SET NAMES utf8mb4로도 해결 안 됨. 다른 문제',
            'next_step' => 'diagnose_update_failure.php와 Moodle 로그 확인 필요'
        ];
    }

    $result['success'] = true;

} catch (Exception $e) {
    $result['success'] = false;
    $result['error'] = $e->getMessage();
    $result['error_class'] = get_class($e);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
