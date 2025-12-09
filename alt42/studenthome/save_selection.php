<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

// POST 데이터 받기
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 디버깅을 위한 로그
error_log("Save selection input: " . $input);

// index3 특별 디버깅
if ($data && $data['page_type'] === 'index3') {
    error_log("Index3 save data: " . json_encode($data));
}

if (!$data || !isset($data['page_type'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data', 'received' => $data]);
    exit;
}

$studentid = isset($data['userid']) ? $data['userid'] : $USER->id;
$page_type = $data['page_type'];
$selection_data = isset($data['selection_data']) ? json_encode($data['selection_data']) : '{}';
$last_path = isset($data['last_path']) ? $data['last_path'] : '';
$last_unit = isset($data['last_unit']) ? $data['last_unit'] : '';
$last_topic = isset($data['last_topic']) ? $data['last_topic'] : '';

// 기존 레코드 확인
$existing = $DB->get_record('user_learning_selections', 
    array('userid' => $studentid, 'page_type' => $page_type)
);

$record = new stdClass();
$record->userid = $studentid;
$record->page_type = $page_type;
$record->selection_data = $selection_data;
$record->last_path = $last_path;
$record->last_unit = $last_unit;
$record->last_topic = $last_topic;
$record->timemodified = time();

if ($existing) {
    // 업데이트
    $record->id = $existing->id;
    $DB->update_record('user_learning_selections', $record);
} else {
    // 새로 생성
    $record->timecreated = time();
    $DB->insert_record('user_learning_selections', $record);
}

echo json_encode([
    'success' => true, 
    'saved_data' => [
        'userid' => $studentid,
        'page_type' => $page_type,
        'last_unit' => $last_unit,
        'last_topic' => $last_topic
    ]
]);
?>