<?php
// stickynotes_api.php - 간단한 포스트잇 메모 CRUD API
// 요청 파라미터:
//   action=list|add|update|delete
//   userid (필수)
// 추가 파라미터:
//   text (add/update), noteid (update/delete)

require_once("/home/moodle/public_html/moodle/config.php");
require_login();

global $DB, $USER;

header('Content-Type: application/json; charset=utf-8');

$action = optional_param('action', 'list', PARAM_ALPHA);
$userid = optional_param('userid', $USER->id, PARAM_INT);

// 실제 테이블 명 (Moodle 프리픽스 포함)
$table = 'abessi_postit';

// 존재 여부 확인 (없으면 에러 반환)
if (!$DB->get_manager()->table_exists(new xmldb_table($table))) {
    echo json_encode(['success'=>false,'error'=>'table_not_found']);
    exit;
}

$now   = time();

switch ($action) {
    case 'add':
        $text = required_param('text', PARAM_RAW);
        $record            = new stdClass();
        $record->userid    = $userid;
        $record->text      = $text;
        $record->status    = 'begin';
        $record->timecreated = $now;
        $record->timemodified = $now;
        $id = $DB->insert_record($table, $record);
        echo json_encode(['success' => true, 'id' => $id]);
        break;
    case 'update':
        $noteid = required_param('noteid', PARAM_INT);
        $text   = required_param('text', PARAM_RAW);
        $status = optional_param('status', null, PARAM_ALPHA);
        $record = $DB->get_record($table, ['id' => $noteid, 'userid' => $userid], '*', MUST_EXIST);
        $record->text = $text;
        if ($status) $record->status = $status;
        $record->timemodified = $now;
        $DB->update_record($table, $record);
        echo json_encode(['success' => true]);
        break;
    case 'delete':
        $noteid = required_param('noteid', PARAM_INT);
        $DB->delete_records($table, ['id' => $noteid, 'userid' => $userid]);
        echo json_encode(['success' => true]);
        break;
    case 'list':
    default:
        // 자동 outdated 갱신 (2주 경과)
        $threshold = $now - 14 * 24 * 60 * 60; // 2주(14일)
        $notes = $DB->get_records($table, ['userid' => $userid]);
        foreach ($notes as $n) {
            if ($n->status !== 'outdated' && $n->timecreated < $threshold) {
                $n->status = 'outdated';
                $n->timemodified = $now;
                $DB->update_record($table, $n);
            }
        }
        // 다시 가져와 정렬 : outdated 마지막
        $rows = $DB->get_records_sql("SELECT * FROM {$table} WHERE userid=? ORDER BY (status='outdated'), timecreated DESC", [$userid]);
        $response = array_values($rows); // reindex
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        break;
}
?> 