<?php
/**
 * File: check_existing_content.php
 * Purpose: DB에 저장된 기존 컨텐츠 확인
 * Location: /mnt/c/1 Project/augmented_teacher/books/check_existing_content.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    // POST 데이터 받기
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    error_log(sprintf(
        '[check_existing_content.php] File: %s, Line: %d, Request received',
        basename(__FILE__),
        __LINE__
    ));

    if (!isset($data['contentsid']) || !isset($data['contentstype']) || !isset($data['nstep'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $contentsid = $data['contentsid'];
    $contentstype = $data['contentstype'];
    $nstep = intval($data['nstep']);

    // DB에서 기존 레코드 확인
    $existingRecord = $DB->get_record('abessi_tailoredcontents', array(
        'contentsid' => $contentsid,
        'contentstype' => $contentstype,
        'nstep' => $nstep
    ));

    if ($existingRecord) {
        // 기존 데이터 존재
        error_log(sprintf(
            '[check_existing_content.php] File: %s, Line: %d, Found existing record id=%d',
            basename(__FILE__),
            __LINE__,
            $existingRecord->id
        ));

        echo json_encode(array(
            'success' => true,
            'exists' => true,
            'thinking' => $existingRecord->qstn0,
            'questions' => array(
                $existingRecord->qstn1,
                $existingRecord->qstn2,
                $existingRecord->qstn3
            ),
            'answers' => array(
                $existingRecord->ans1,
                $existingRecord->ans2,
                $existingRecord->ans3
            )
        ), JSON_UNESCAPED_UNICODE);
    } else {
        // 기존 데이터 없음
        error_log(sprintf(
            '[check_existing_content.php] File: %s, Line: %d, No existing record found',
            basename(__FILE__),
            __LINE__
        ));

        echo json_encode(array(
            'success' => true,
            'exists' => false
        ), JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    error_log(sprintf(
        '[check_existing_content.php] File: %s, Line: %d, Error: %s',
        basename(__FILE__),
        __LINE__,
        $e->getMessage()
    ));

    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
