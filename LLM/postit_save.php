<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

if (!isset($_POST['ajax_action']) || $_POST['ajax_action'] !== 'save_postit') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$ajax_studentid = isset($_POST['studentid']) ? intval($_POST['studentid']) : 0;
$ajax_pagetype = isset($_POST['pagetype']) ? trim($_POST['pagetype']) : '';
$content = isset($_POST['content']) ? $_POST['content'] : '';

if ($ajax_studentid && $ajax_pagetype !== '') {
    try {
        // 기존 레코드 확인
        $existing = $DB->get_record_sql(
            "SELECT * FROM {abessi_stickynotes} WHERE userid = ? AND type = ? AND hide = 0", 
            array($ajax_studentid, $ajax_pagetype)
        );
        
        if ($existing) {
            // 기존 레코드를 숨김 처리
            $existing->hide = 1;
            $DB->update_record('abessi_stickynotes', $existing);
            
            // 새로운 레코드 생성 (기존 정보 복사 + 내용 변경)
            $record = new stdClass();
            $record->userid = $existing->userid;
            $record->authorid = $USER->id;
            $record->type = $existing->type;
            $record->content = $content;
            $record->color = $existing->color;
            $record->created_at = $existing->created_at;
            $record->updated_at = time();
            $record->hide = 0;
            $record->image = $existing->image;
            
            if ($DB->insert_record('abessi_stickynotes', $record)) {
                echo json_encode(['success' => true, 'message' => '메모가 수정되었습니다.']);
            } else {
                echo json_encode(['success' => false, 'message' => '수정 실패: DB 저장 오류']);
            }
        } else {
            // 새로 생성
            $record = new stdClass();
            $record->userid = $ajax_studentid;
            $record->authorid = $USER->id;
            $record->type = $ajax_pagetype;
            $record->content = $content;
            $record->color = 'yellow';
            $record->created_at = time();
            $record->updated_at = time();
            $record->hide = 0;
            
            if ($DB->insert_record('abessi_stickynotes', $record)) {
                echo json_encode(['success' => true, 'message' => '메모가 저장되었습니다.']);
            } else {
                echo json_encode(['success' => false, 'message' => '저장 실패: DB 저장 오류']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '오류 발생: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => '필수 정보가 부족합니다. (studentid: '.$ajax_studentid.', pagetype: '.$ajax_pagetype.')']);
}
?> 