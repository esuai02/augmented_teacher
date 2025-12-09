<?php
/**
 * UTF-8mb4 마이그레이션 스크립트
 * 파일: migrate_to_utf8mb4.php
 * 목적: report_html 및 report_data 컬럼을 utf8mb4로 안전하게 변환
 * 날짜: 2025-11-13
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

$result = [
    'migration_name' => 'UTF-8mb4 Conversion',
    'timestamp' => date('Y-m-d H:i:s'),
    'file' => __FILE__,
    'line' => __LINE__
];

try {
    $tableName = 'alt42_goinghome_reports';
    $fullTableName = 'mdl_' . $tableName;

    // ============================================================
    // Step 1: 현재 인코딩 상태 확인
    // ============================================================
    $result['step_1'] = '현재 인코딩 상태 확인';

    $currentEncoding = $DB->get_records_sql("
        SELECT
            COLUMN_NAME,
            CHARACTER_SET_NAME,
            COLLATION_NAME,
            DATA_TYPE,
            CHARACTER_MAXIMUM_LENGTH
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME IN ('report_html', 'report_data')
    ", [$fullTableName]);

    if (empty($currentEncoding)) {
        $result['error'] = '컬럼 정보를 조회할 수 없습니다';
        $result['success'] = false;
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result['current_encoding'] = [];
    foreach ($currentEncoding as $col) {
        $result['current_encoding'][$col->column_name] = [
            'charset' => $col->character_set_name,
            'collation' => $col->collation_name,
            'type' => $col->data_type,
            'max_length' => $col->character_maximum_length
        ];
    }

    // ============================================================
    // Step 2: 이미 utf8mb4인지 확인
    // ============================================================
    $result['step_2'] = '변환 필요 여부 확인';

    $needsMigration = false;
    $columnsToMigrate = [];

    foreach ($currentEncoding as $col) {
        if ($col->character_set_name !== 'utf8mb4') {
            $needsMigration = true;
            $columnsToMigrate[] = $col->column_name;
        }
    }

    $result['needs_migration'] = $needsMigration;
    $result['columns_to_migrate'] = $columnsToMigrate;

    if (!$needsMigration) {
        $result['message'] = '이미 utf8mb4로 설정되어 있습니다. 마이그레이션이 필요하지 않습니다.';
        $result['success'] = true;
        $result['overall_status'] = 'ALREADY_UTF8MB4';
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================================================
    // Step 3: 백업 권장 메시지
    // ============================================================
    $result['step_3'] = '백업 권장 사항';
    $result['backup_recommendation'] = [
        'message' => '마이그레이션 전 데이터베이스 백업을 권장합니다',
        'command' => "mysqldump -u [username] -p [database_name] {$fullTableName} > backup_{$tableName}_" . date('Ymd') . ".sql",
        'table_name' => $fullTableName,
        'record_count' => $DB->count_records($tableName)
    ];

    // ============================================================
    // Step 4: report_html 컬럼 변환
    // ============================================================
    $result['step_4'] = 'report_html 컬럼 변환';

    try {
        $sql = "ALTER TABLE {$fullTableName}
                MODIFY COLUMN report_html LONGTEXT
                CHARACTER SET utf8mb4
                COLLATE utf8mb4_unicode_ci
                COMMENT '리포트 HTML (이모지 포함 가능)'";

        $DB->execute($sql);
        $result['report_html_migration'] = 'SUCCESS';

    } catch (dml_exception $e) {
        $result['report_html_migration'] = 'FAILED';
        $result['report_html_error'] = $e->getMessage();
        $result['overall_status'] = 'PARTIAL_FAIL';
    }

    // ============================================================
    // Step 5: report_data 컬럼 변환
    // ============================================================
    $result['step_5'] = 'report_data 컬럼 변환';

    try {
        $sql = "ALTER TABLE {$fullTableName}
                MODIFY COLUMN report_data LONGTEXT
                CHARACTER SET utf8mb4
                COLLATE utf8mb4_unicode_ci
                COMMENT '리포트 JSON 데이터 (이모지 포함 가능)'";

        $DB->execute($sql);
        $result['report_data_migration'] = 'SUCCESS';

    } catch (dml_exception $e) {
        $result['report_data_migration'] = 'FAILED';
        $result['report_data_error'] = $e->getMessage();
        $result['overall_status'] = 'PARTIAL_FAIL';
    }

    // ============================================================
    // Step 6: 변환 결과 확인
    // ============================================================
    $result['step_6'] = '변환 결과 확인';

    $newEncoding = $DB->get_records_sql("
        SELECT
            COLUMN_NAME,
            CHARACTER_SET_NAME,
            COLLATION_NAME,
            DATA_TYPE
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME IN ('report_html', 'report_data')
    ", [$fullTableName]);

    $result['new_encoding'] = [];
    $allUtf8mb4 = true;

    foreach ($newEncoding as $col) {
        $result['new_encoding'][$col->column_name] = [
            'charset' => $col->character_set_name,
            'collation' => $col->collation_name,
            'type' => $col->data_type
        ];

        if ($col->character_set_name !== 'utf8mb4') {
            $allUtf8mb4 = false;
        }
    }

    $result['all_columns_utf8mb4'] = $allUtf8mb4;

    // ============================================================
    // Step 7: 이모지 테스트 데이터 삽입
    // ============================================================
    $result['step_7'] = '이모지 테스트 데이터 삽입';

    if ($allUtf8mb4) {
        try {
            // 기본 레코드 INSERT
            $testRecord = new stdClass();
            $testRecord->userid = 1951;
            $testRecord->report_id = 'TEST_UTF8MB4_' . time();
            $testRecord->report_html = '';
            $testRecord->report_data = '';
            $testRecord->report_date = date('Y년 n월 j일');
            $testRecord->timecreated = time();
            $testRecord->timemodified = time();

            $insertId = $DB->insert_record($tableName, $testRecord, true);
            $result['test_insert_id'] = $insertId;

            // 이모지 포함 HTML UPDATE
            $testHtml = '<div><h1>테스트 리포트 😊</h1><p>오늘의 기분: 😄😃😁</p><p>활동: 🏃‍♂️⚽🎮</p></div>';

            $updateRecord = new stdClass();
            $updateRecord->id = $insertId;
            $updateRecord->report_html = $testHtml;
            $updateRecord->timemodified = time();

            $updateSuccess = $DB->update_record($tableName, $updateRecord);
            $result['emoji_test_update'] = $updateSuccess ? 'SUCCESS' : 'FAILED';

            // 저장된 데이터 확인
            $retrieved = $DB->get_record($tableName, ['id' => $insertId]);

            if ($retrieved && !empty($retrieved->report_html)) {
                $result['emoji_preserved'] = (strpos($retrieved->report_html, '😊') !== false);
                $result['retrieved_html_length'] = strlen($retrieved->report_html);
                $result['emoji_test_result'] = $result['emoji_preserved'] ? 'SUCCESS' : 'FAILED';
            } else {
                $result['emoji_test_result'] = 'RETRIEVAL_FAILED';
            }

            // 테스트 데이터 삭제
            $DB->delete_records($tableName, ['id' => $insertId]);
            $result['test_cleanup'] = 'SUCCESS';

        } catch (dml_exception $e) {
            $result['emoji_test_error'] = $e->getMessage();
            $result['emoji_test_result'] = 'EXCEPTION';
        }
    } else {
        $result['emoji_test_result'] = 'SKIPPED (변환 실패)';
    }

    // ============================================================
    // Final Status
    // ============================================================
    if ($allUtf8mb4 && ($result['emoji_test_result'] ?? '') === 'SUCCESS') {
        $result['success'] = true;
        $result['overall_status'] = 'MIGRATION_SUCCESS';
        $result['message'] = 'UTF-8mb4 마이그레이션 완료. 이모지 저장 가능합니다.';
        $result['next_steps'] = [
            '1. index.php에서 복잡한 이모지 처리 로직 제거 (553-567줄)',
            '2. INSERT → UPDATE JSON → UPDATE HTML 패턴 구현',
            '3. 실제 userid=1951로 end-to-end 테스트'
        ];
    } else {
        $result['success'] = false;
        $result['overall_status'] = 'MIGRATION_FAILED';
        $result['message'] = '마이그레이션이 완전히 성공하지 못했습니다. 로그를 확인하세요.';
    }

} catch (Exception $e) {
    $result['success'] = false;
    $result['error_type'] = get_class($e);
    $result['error_message'] = $e->getMessage();
    $result['error_file'] = $e->getFile();
    $result['error_line'] = $e->getLine();
    $result['overall_status'] = 'FATAL_ERROR';
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
