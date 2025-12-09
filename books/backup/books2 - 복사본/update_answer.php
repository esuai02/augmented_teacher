<?php
/**
 * File: update_answer.php
 * Purpose: 답변 편집 후 DB 업데이트
 * Location: /mnt/c/1 Project/augmented_teacher/books/update_answer.php
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
        '[update_answer.php] File: %s, Line: %d, Request received',
        basename(__FILE__),
        __LINE__
    ));

    if (!isset($data['contentsid']) || !isset($data['contentstype']) || !isset($data['nstep']) || !isset($data['answerIndex']) || !isset($data['newAnswer'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $contentsid = $data['contentsid'];
    $contentstype = $data['contentstype'];
    $nstep = intval($data['nstep']);
    $answerIndex = intval($data['answerIndex']);
    $newAnswer = $data['newAnswer'];

    // DB에서 기존 레코드 확인
    $existingRecord = $DB->get_record('abessi_tailoredcontents', array(
        'contentsid' => $contentsid,
        'contentstype' => $contentstype,
        'nstep' => $nstep
    ));

    if (!$existingRecord) {
        throw new Exception('해당 레코드를 찾을 수 없습니다.');
    }

    // 답변 인덱스에 따라 해당 필드 업데이트
    $answerField = 'ans' . ($answerIndex + 1);

    $record = new stdClass();
    $record->id = $existingRecord->id;
    $record->$answerField = $newAnswer;
    $record->timemodified = time();

    $DB->update_record('abessi_tailoredcontents', $record);

    error_log(sprintf(
        '[update_answer.php] File: %s, Line: %d, Answer updated: id=%d, field=%s',
        basename(__FILE__),
        __LINE__,
        $record->id,
        $answerField
    ));

    echo json_encode(array(
        'success' => true
    ), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log(sprintf(
        '[update_answer.php] File: %s, Line: %d, Error: %s',
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
