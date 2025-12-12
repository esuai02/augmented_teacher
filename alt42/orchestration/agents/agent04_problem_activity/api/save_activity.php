<?php
/**
 * File: alt42/orchestration/agents/agent04_problem_activity/api/save_activity.php
 * 활동 선택 데이터 저장 API
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    // POST 데이터 파싱
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['main_category'])) {
        throw new Exception('Invalid input: main_category required');
    }

    // 사용자 ID 결정
    $userid = $input['userid'] ?? $USER->id;

    // 데이터 준비
    $record = new stdClass();
    $record->userid = $userid;
    $record->main_category = $input['main_category'];
    $record->sub_activity = $input['sub_activity'] ?? null;
    $record->behavior_type = $input['behavior_type'] ?? null;
    $record->survey_responses = isset($input['survey_responses'])
        ? json_encode($input['survey_responses'])
        : null;

    // 기존 레코드 확인 (오늘 날짜 기준)
    $today_start = strtotime('today');
    $existing = $DB->get_record_sql(
        "SELECT id FROM mdl_alt42_student_activity
         WHERE userid = ? AND main_category = ? AND created_at >= FROM_UNIXTIME(?)
         ORDER BY created_at DESC LIMIT 1",
        [$userid, $record->main_category, $today_start]
    );

    if ($existing) {
        // 업데이트
        $record->id = $existing->id;
        $DB->update_record('alt42_student_activity', $record);
        $message = 'Activity updated';
    } else {
        // 신규 삽입
        $record->id = $DB->insert_record('alt42_student_activity', $record);
        $message = 'Activity saved';
    }

    echo json_encode([
        'status' => 'ok',
        'message' => $message,
        'id' => $record->id,
        'data' => $record
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ]);
}
