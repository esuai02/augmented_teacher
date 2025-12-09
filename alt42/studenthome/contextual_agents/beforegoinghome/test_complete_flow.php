<?php
/**
 * 완전한 플로우 End-to-End 테스트
 * 파일: test_complete_flow.php
 * 목적: 마이그레이션 후 실제 save_report 로직 검증
 * 날짜: 2025-11-13
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// UTF-8mb4 연결 설정 (이모지 지원)
// 각 PHP 스크립트는 새로운 DB 연결을 시작하므로 매번 설정 필요
try {
    $DB->execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    error_log("Failed to set connection charset to utf8mb4 at " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
}

header('Content-Type: application/json; charset=utf-8');

$result = [
    'test_name' => 'Complete Flow End-to-End Test',
    'timestamp' => date('Y-m-d H:i:s'),
    'file' => __FILE__,
    'line' => __LINE__
];

try {
    $tableName = 'alt42_goinghome_reports';

    // ============================================================
    // Test 1: UTF-8mb4 인코딩 확인
    // ============================================================
    $result['test_1'] = 'UTF-8mb4 인코딩 확인';

    $encoding = $DB->get_records_sql("
        SELECT
            COLUMN_NAME,
            CHARACTER_SET_NAME,
            COLLATION_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME IN ('report_html', 'report_data')
    ", ['mdl_' . $tableName]);

    $result['encoding_check'] = [];
    $allUtf8mb4 = true;

    foreach ($encoding as $col) {
        $result['encoding_check'][$col->column_name] = [
            'charset' => $col->character_set_name,
            'collation' => $col->collation_name
        ];

        if ($col->character_set_name !== 'utf8mb4') {
            $allUtf8mb4 = false;
        }
    }

    $result['test_1_result'] = $allUtf8mb4 ? 'PASS' : 'FAIL';

    if (!$allUtf8mb4) {
        $result['error'] = 'UTF-8mb4 마이그레이션이 완료되지 않았습니다';
        $result['overall_status'] = 'MIGRATION_REQUIRED';
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================================================
    // Test 2: Progressive Update 패턴 테스트
    // ============================================================
    $result['test_2'] = 'Progressive Update 패턴 테스트';

    $testStudentId = 1951;
    $reportId = 'TEST_COMPLETE_' . time() . '_' . substr(md5(uniqid()), 0, 8);

    // 테스트 데이터 준비
    $testResponses = [
        ['question' => '오늘 기분은 어떤가요?', 'answer' => '아주 좋아요! 😊😄'],
        ['question' => '어떤 활동을 했나요?', 'answer' => '축구를 했어요 ⚽ 그리고 게임도 했어요 🎮'],
        ['question' => '내일 계획은?', 'answer' => '친구들과 놀 거예요 🏃‍♂️']
    ];

    $reportData = [
        'student_id' => $testStudentId,
        'student_name' => '테스트학생',
        'responses' => $testResponses,
        'report_id' => $reportId,
        'created_at' => time()
    ];

    $reportHtml = '<div class="report">';
    $reportHtml .= '<h1>귀가검사 리포트 😊</h1>';
    $reportHtml .= '<p>학생: 테스트학생 (ID: ' . $testStudentId . ')</p>';
    $reportHtml .= '<div class="responses">';
    foreach ($testResponses as $i => $resp) {
        $reportHtml .= '<div class="response">';
        $reportHtml .= '<h3>질문 ' . ($i + 1) . ': ' . htmlspecialchars($resp['question']) . '</h3>';
        $reportHtml .= '<p>답변: ' . htmlspecialchars($resp['answer']) . '</p>';
        $reportHtml .= '</div>';
    }
    $reportHtml .= '</div>';
    $reportHtml .= '<p>생성 시간: ' . date('Y-m-d H:i:s') . '</p>';
    $reportHtml .= '</div>';

    // JSON 데이터 준비
    $jsonData = json_encode($reportData, JSON_UNESCAPED_UNICODE);
    $jsonSize = strlen($jsonData);
    $htmlSize = strlen($reportHtml);

    $result['test_data_sizes'] = [
        'json_size' => $jsonSize,
        'json_size_kb' => round($jsonSize / 1024, 2),
        'html_size' => $htmlSize,
        'html_size_kb' => round($htmlSize / 1024, 2),
        'contains_emoji' => (preg_match('/[\x{1F600}-\x{1F64F}]/u', $reportHtml) ? 'yes' : 'no')
    ];

    // Step 2a: 기본 레코드 INSERT
    try {
        $record = new stdClass();
        $record->userid = $testStudentId;
        $record->report_id = $reportId;
        $record->report_html = '';
        $record->report_data = '';
        $record->report_date = date('Y년 n월 j일');
        $record->timecreated = time();
        $record->timemodified = time();

        $insertId = $DB->insert_record($tableName, $record, true);

        if ($insertId > 0) {
            $result['step_2a_insert'] = 'SUCCESS';
            $result['insert_id'] = $insertId;
        } else {
            $result['step_2a_insert'] = 'FAILED';
            $result['test_2_result'] = 'FAIL';
        }

    } catch (Exception $e) {
        $result['step_2a_insert'] = 'EXCEPTION';
        $result['step_2a_error'] = $e->getMessage();
        $result['test_2_result'] = 'FAIL';
    }

    // Step 2b: JSON 데이터 UPDATE
    if (($result['step_2a_insert'] ?? '') === 'SUCCESS') {
        try {
            $updateJson = new stdClass();
            $updateJson->id = $insertId;
            $updateJson->report_data = $jsonData;
            $updateJson->timemodified = time();

            $jsonUpdateSuccess = $DB->update_record($tableName, $updateJson);

            $result['step_2b_json_update'] = $jsonUpdateSuccess ? 'SUCCESS' : 'FAILED';

        } catch (Exception $e) {
            $result['step_2b_json_update'] = 'EXCEPTION';
            $result['step_2b_error'] = $e->getMessage();
        }
    }

    // Step 2c: HTML 데이터 UPDATE
    if (($result['step_2b_json_update'] ?? '') === 'SUCCESS') {
        try {
            $updateHtml = new stdClass();
            $updateHtml->id = $insertId;
            $updateHtml->report_html = $reportHtml;
            $updateHtml->timemodified = time();

            $htmlUpdateSuccess = $DB->update_record($tableName, $updateHtml);

            $result['step_2c_html_update'] = $htmlUpdateSuccess ? 'SUCCESS' : 'FAILED';

        } catch (Exception $e) {
            $result['step_2c_html_update'] = 'EXCEPTION';
            $result['step_2c_error'] = $e->getMessage();
            $result['step_2c_error_detail'] = [
                'error_class' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ];
        }
    }

    // 전체 결과 평가
    $test2Success = (
        ($result['step_2a_insert'] ?? '') === 'SUCCESS' &&
        ($result['step_2b_json_update'] ?? '') === 'SUCCESS' &&
        ($result['step_2c_html_update'] ?? '') === 'SUCCESS'
    );

    $result['test_2_result'] = $test2Success ? 'PASS' : 'FAIL';

    // ============================================================
    // Test 3: 데이터 무결성 검증
    // ============================================================
    $result['test_3'] = '데이터 무결성 검증';

    if ($test2Success) {
        try {
            $retrieved = $DB->get_record($tableName, ['id' => $insertId]);

            if ($retrieved) {
                // JSON 데이터 검증
                $retrievedJsonData = json_decode($retrieved->report_data, true);
                $jsonIntegrity = ($retrievedJsonData['report_id'] === $reportId);

                // HTML 데이터 검증
                $htmlIntegrity = (strlen($retrieved->report_html) > 0);

                // 이모지 보존 확인
                $emojiPreserved = (
                    strpos($retrieved->report_html, '😊') !== false &&
                    strpos($retrieved->report_html, '😄') !== false &&
                    strpos($retrieved->report_html, '⚽') !== false &&
                    strpos($retrieved->report_html, '🎮') !== false
                );

                $result['data_integrity'] = [
                    'json_valid' => $jsonIntegrity,
                    'html_not_empty' => $htmlIntegrity,
                    'emoji_preserved' => $emojiPreserved,
                    'retrieved_html_length' => strlen($retrieved->report_html),
                    'retrieved_json_length' => strlen($retrieved->report_data)
                ];

                $result['test_3_result'] = (
                    $jsonIntegrity && $htmlIntegrity && $emojiPreserved
                ) ? 'PASS' : 'FAIL';

                // 이모지가 실제로 저장되었는지 샘플 표시
                $result['emoji_samples'] = [
                    'found_in_html' => [
                        '😊' => (strpos($retrieved->report_html, '😊') !== false),
                        '😄' => (strpos($retrieved->report_html, '😄') !== false),
                        '⚽' => (strpos($retrieved->report_html, '⚽') !== false),
                        '🎮' => (strpos($retrieved->report_html, '🎮') !== false),
                        '🏃‍♂️' => (strpos($retrieved->report_html, '🏃‍♂️') !== false)
                    ]
                ];

            } else {
                $result['test_3_result'] = 'FAIL';
                $result['error'] = '저장된 데이터를 조회할 수 없습니다';
            }

        } catch (Exception $e) {
            $result['test_3_result'] = 'EXCEPTION';
            $result['test_3_error'] = $e->getMessage();
        }
    } else {
        $result['test_3_result'] = 'SKIPPED (Test 2 실패)';
    }

    // ============================================================
    // Test 4: 정리
    // ============================================================
    $result['test_4'] = '테스트 데이터 정리';

    try {
        if (isset($insertId) && $insertId > 0) {
            $DB->delete_records($tableName, ['id' => $insertId]);
            $result['test_4_result'] = 'SUCCESS';
        } else {
            $result['test_4_result'] = 'SKIPPED (삽입된 레코드 없음)';
        }
    } catch (Exception $e) {
        $result['test_4_result'] = 'FAILED';
        $result['test_4_error'] = $e->getMessage();
    }

    // ============================================================
    // 최종 결과
    // ============================================================

    $allTestsPassed = (
        ($result['test_1_result'] ?? '') === 'PASS' &&
        ($result['test_2_result'] ?? '') === 'PASS' &&
        ($result['test_3_result'] ?? '') === 'PASS'
    );

    $result['success'] = $allTestsPassed;
    $result['overall_status'] = $allTestsPassed ? 'ALL_TESTS_PASSED' : 'SOME_TESTS_FAILED';

    if ($allTestsPassed) {
        $result['conclusion'] = [
            'message' => 'UTF-8mb4 마이그레이션 완료 및 Progressive Update 패턴 정상 작동',
            'emoji_support' => 'CONFIRMED',
            'ready_for_production' => true,
            'next_steps' => [
                '1. index.php에 새로운 코드 적용',
                '2. 실제 userid=1951로 end-to-end 테스트',
                '3. 24시간 모니터링 시작'
            ]
        ];
    } else {
        $result['conclusion'] = [
            'message' => '일부 테스트 실패. 로그를 확인하세요.',
            'failed_tests' => array_filter([
                ($result['test_1_result'] ?? '') !== 'PASS' ? 'UTF-8mb4 인코딩' : null,
                ($result['test_2_result'] ?? '') !== 'PASS' ? 'Progressive Update' : null,
                ($result['test_3_result'] ?? '') !== 'PASS' ? '데이터 무결성' : null
            ])
        ];
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
