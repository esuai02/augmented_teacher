<?php
/**
 * File: alt42/orchestration/agents/agent04_problem_activity/api/get_activity.php
 * 활동 선택 데이터 조회 API
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    $userid = $_GET['userid'] ?? $USER->id;
    $limit = $_GET['limit'] ?? 10;

    // 최근 활동 조회
    $records = $DB->get_records_sql(
        "SELECT * FROM mdl_alt42_student_activity
         WHERE userid = ?
         ORDER BY created_at DESC
         LIMIT ?",
        [$userid, $limit]
    );

    // survey_responses JSON 디코딩
    foreach ($records as &$record) {
        if ($record->survey_responses) {
            $record->survey_responses = json_decode($record->survey_responses, true);
        }
    }

    echo json_encode([
        'status' => 'ok',
        'count' => count($records),
        'data' => array_values($records)
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
