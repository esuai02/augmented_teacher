<?php
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;

header('Content-Type: application/json');

// POST 체크
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청 방식입니다.']);
    exit;
}

// JSON 파싱
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['userid'], $data['taskid'], $data['field'], $data['value'])) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터 누락']);
    exit;
}

$userid = intval($data['userid']);
$taskid = intval($data['taskid']);
$field  = $data['field'];
$value  = $data['value'];

// 업데이트 가능 컬럼 (rsc1~rsc9 포함)
$allowed_fields = [
  'wxspert','memo','okr','kpi',
  'qstn',
  'prompt1','prompt2','prompt3','jsonfile',
  'rsc1','rsc2','rsc3','rsc4','rsc5','rsc6','rsc7','rsc8','rsc9'
];

if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => '허용되지 않은 컬럼입니다.']);
    exit;
}

try {
    // 기존 레코드 조회
    $sql = "SELECT * FROM {agent_dashboard_memos} WHERE user_id = ? AND taskid = ?";
    $existing = $DB->get_record_sql($sql, array($userid, $taskid));

    if ($existing) {
        // 해당 컬럼만 갱신
        $existing->$field = $value;
        $existing->timemodified = time();
        $DB->update_record('agent_dashboard_memos', $existing);
    } else {
        // 새 Insert
        $new = new stdClass();
        $new->user_id = $userid;
        $new->taskid  = $taskid;
        $new->$field  = $value;
        $new->timecreated = time();
        $new->timemodified = time();
        $DB->insert_record('agent_dashboard_memos', $new);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('DB update error: '.$e->getMessage());
    echo json_encode(['success' => false, 'message' => 'DB 업데이트 오류']);
}
