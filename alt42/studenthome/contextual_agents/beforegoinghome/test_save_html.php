<?php
/**
 * HTML 데이터 UPDATE 테스트
 * 파일: test_save_html.php
 * 목적: INSERT 후 report_html 필드 UPDATE 성공 여부 확인 (이모지 포함/제외)
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

$result = [
    'test_name' => 'HTML Data UPDATE Test',
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

    // 2. 테스트 HTML 데이터 준비 (4가지 케이스)
    $testCases = [
        'simple' => [
            'name' => '간단한 HTML',
            'html' => '<div><h1>테스트 리포트</h1><p>간단한 내용입니다.</p></div>',
            'has_emoji' => false
        ],
        'with_korean' => [
            'name' => '한글 포함 HTML',
            'html' => '<div><h1>귀가검사 리포트</h1><p>학생 이름: 홍길동</p><p>날짜: ' . date('Y년 n월 j일') . '</p></div>',
            'has_emoji' => false
        ],
        'with_emoji' => [
            'name' => '이모지 포함 HTML',
            'html' => '<div><h1>리포트 😊</h1><p>오늘의 기분: 😄😃😁</p><p>활동: 🏃‍♂️⚽🎮</p></div>',
            'has_emoji' => true
        ],
        'large' => [
            'name' => '큰 HTML (1MB)',
            'html' => '<div><h1>대용량 리포트</h1>' . str_repeat('<p>반복되는 내용입니다. </p>', 50000) . '</div>',
            'has_emoji' => false
        ]
    ];

    $result['test_cases'] = [];

    foreach ($testCases as $testKey => $testCase) {
        $caseResult = ['name' => $testCase['name'], 'key' => $testKey];

        try {
            // 3. 기본 레코드 INSERT
            $record = new stdClass();
            $record->userid = 1951;
            $record->report_id = 'TEST_HTML_' . strtoupper($testKey) . '_' . time();
            $record->report_html = '';
            $record->report_data = '';
            $record->report_date = date('Y년 n월 j일');
            $record->timecreated = time();
            $record->timemodified = time();

            $insertId = $DB->insert_record($tableName, $record, true);
            $caseResult['insert_success'] = ($insertId > 0);
            $caseResult['insert_id'] = $insertId;

            if ($insertId > 0) {
                // 4. HTML 데이터 준비
                $htmlData = $testCase['html'];
                $htmlSize = strlen($htmlData);
                $caseResult['html_size'] = $htmlSize;
                $caseResult['html_size_kb'] = round($htmlSize / 1024, 2);
                $caseResult['has_emoji'] = $testCase['has_emoji'];

                // 5. 크기 검증
                $maxSize = 4 * 1024 * 1024; // 4MB
                if ($htmlSize > $maxSize) {
                    $htmlData = substr($htmlData, 0, $maxSize);
                    $caseResult['html_truncated'] = true;
                } else {
                    $caseResult['html_truncated'] = false;
                }

                // 6. UPDATE 시도 (이모지 처리 로직 없이)
                $updateRecord = new stdClass();
                $updateRecord->id = $insertId;
                $updateRecord->report_html = $htmlData;
                $updateRecord->timemodified = time();

                $updateSuccess = $DB->update_record($tableName, $updateRecord);
                $caseResult['update_success'] = $updateSuccess;

                if ($updateSuccess) {
                    // 7. 업데이트된 데이터 확인
                    $retrieved = $DB->get_record($tableName, ['id' => $insertId]);

                    if ($retrieved && !empty($retrieved->report_html)) {
                        $caseResult['retrieve_success'] = true;
                        $caseResult['retrieved_size'] = strlen($retrieved->report_html);

                        // 이모지가 그대로 저장되었는지 확인
                        if ($testCase['has_emoji']) {
                            $caseResult['emoji_preserved'] = (strpos($retrieved->report_html, '😊') !== false);
                        }

                        // 데이터 무결성 검증 (크기 비교)
                        $sizeDiff = abs(strlen($retrieved->report_html) - strlen($htmlData));
                        $caseResult['size_difference'] = $sizeDiff;
                        $caseResult['data_integrity'] = ($sizeDiff < 10); // 10바이트 이내 차이 허용

                    } else {
                        $caseResult['retrieve_success'] = false;
                        $caseResult['error'] = 'UPDATE된 HTML을 조회할 수 없습니다';
                    }
                }

                // 8. 정리
                $DB->delete_records($tableName, ['id' => $insertId]);
                $caseResult['cleanup_success'] = true;

                $caseResult['overall_status'] = ($caseResult['update_success'] && $caseResult['retrieve_success']) ? 'SUCCESS' : 'FAIL';
            } else {
                $caseResult['overall_status'] = 'INSERT_FAIL';
                $caseResult['error'] = 'INSERT 실패';
            }

        } catch (dml_exception $e) {
            $caseResult['error_type'] = 'dml_exception';
            $caseResult['error_message'] = $e->getMessage();
            $caseResult['error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
            $caseResult['error_debuginfo'] = isset($e->debuginfo) ? $e->debuginfo : 'no debug info';
            $caseResult['overall_status'] = 'DML_ERROR';

        } catch (Exception $e) {
            $caseResult['error_type'] = get_class($e);
            $caseResult['error_message'] = $e->getMessage();
            $caseResult['error_file'] = $e->getFile();
            $caseResult['error_line'] = $e->getLine();
            $caseResult['overall_status'] = 'EXCEPTION';
        }

        $result['test_cases'][] = $caseResult;
    }

    // 전체 성공 여부
    $allSuccess = true;
    $emojiTestPassed = false;

    foreach ($result['test_cases'] as $case) {
        if ($case['overall_status'] !== 'SUCCESS') {
            $allSuccess = false;
        }
        if ($case['key'] === 'with_emoji' && $case['overall_status'] === 'SUCCESS') {
            $emojiTestPassed = ($case['emoji_preserved'] ?? false);
        }
    }

    $result['success'] = $allSuccess;
    $result['emoji_test_passed'] = $emojiTestPassed;
    $result['overall_status'] = $allSuccess ? 'ALL_SUCCESS' : 'SOME_FAILED';

    // 이모지 테스트 결과 요약
    if ($emojiTestPassed) {
        $result['recommendation'] = 'utf8mb4 인코딩으로 이모지 저장 가능 - 복잡한 변환 로직 불필요';
    } else {
        $result['recommendation'] = '이모지 저장 실패 - HTML 엔티티 변환 또는 Base64 인코딩 고려 필요';
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
