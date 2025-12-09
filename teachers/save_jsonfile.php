<?php
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청 방식입니다.']);
    exit;
}

// JSON 파싱
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['jsonfile'], $data['fields_to_update'])) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터 누락']);
    exit;
}

$jsonfile          = json_decode($data['jsonfile'], true);
$fields_to_update  = $data['fields_to_update'];
$wxspertMemo = isset($data['wxspert']) ? $data['wxspert'] : '';
$update_all_fields = isset($data['update_all_fields']) ? (bool)$data['update_all_fields'] : false;

// 허용 컬럼
$allowed_fields = [
  'wxspert','okr','kpi','qstn','prompt1','prompt2','prompt3','jsonfile'
];

// userid, taskid 제거
unset($fields_to_update['userid'], $fields_to_update['taskid']);
unset($jsonfile['userid'], $jsonfile['taskid']);

try {
    // 기존 레코드 조회
    $sql = "SELECT * FROM {agent_dashboard_memos} WHERE user_id = ? AND taskid = ?";
    $existing = $DB->get_record_sql($sql, array($data['userid'], $data['taskid']));

    if ($existing) {
        // 기존 레코드 업데이트
        foreach ($fields_to_update as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $existing->$field = $value;
            }
        }
        // wxspertMemo(추가로 메모 필드에 저장)
        if (!empty($wxspertMemo)) {
            $existing->wxspert = $wxspertMemo;
        }
        $existing->timemodified = time();
        $DB->update_record('agent_dashboard_memos', $existing);
    } else {
        // 새 레코드 삽입
        $record = new stdClass();
        $record->user_id = $data['userid'];
        $record->taskid  = $data['taskid'];
        foreach ($fields_to_update as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $record->$field = $value;
            }
        }
        if (!empty($wxspertMemo)) {
            $record->wxspert = $wxspertMemo;
        }
        $record->timecreated   = time();
        $record->timemodified  = time();
        $DB->insert_record('agent_dashboard_memos', $record);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('DB update error: '.$e->getMessage());
    echo json_encode(['success' => false, 'message' => 'JSON 저장 중 DB 오류 발생']);
}
