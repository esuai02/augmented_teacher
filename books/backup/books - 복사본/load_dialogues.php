<?php
// load_dialogues.php

require_once("/home/moodle/public_html/moodle/config.php");
require_login();
global $DB, $USER;

// 입력값 검증
$contentsid = isset($_POST['contentsid']) ? intval($_POST['contentsid']) : 0;
$contentstype = isset($_POST['contentstype']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['contentstype']) : '';

// 사용자 권한 확인 (필요에 따라 수정)
$userrole = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = ? ORDER BY id DESC LIMIT 1", [$USER->id, 22]);
$role = $userrole ? $userrole->data : '';
if ($role === 'student') {
    echo json_encode(['error' => '사용권한이 없습니다.']);
    exit();
}

// 데이터베이스에서 대화와 오디오 정보 가져오기
$dialogues_records = $DB->get_records_sql("
    SELECT dialogue_text, audio_url
    FROM {abrainalignment_dialogues}
    WHERE contentsid = ? AND contentstype = ?
    ORDER BY id ASC
", [$contentsid, $contentstype]);

$dialogues = [];
if ($dialogues_records) {
    foreach ($dialogues_records as $record) {
        $dialogues[] = [
            'text' => $record->dialogue_text,
            'audioUrl' => $record->audio_url
        ];
    }
}

// JSON 형태로 반환
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['dialogues' => $dialogues]);
?>