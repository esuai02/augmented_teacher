<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
// 필요시 로그인 체크
// require_login();

// 전역 객체 선언
global $DB, $USER;

// JSON 입력 파싱
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

// user_id, event_type, engagement_score 추출
$userId = isset($input['user_id']) ? $input['user_id'] : 0;
$eventType = isset($input['event_type']) ? $input['event_type'] : 'UNKNOWN';
$engagementScore = isset($input['engagement_score']) ? $input['engagement_score'] : null;

// Moodle DB 테이블에 넣을 데이터 구성
// 테이블: mdl_alt42_engagement_events
$data = new stdClass();
$data->user_id = $userId;
$data->event_type = $eventType;
$data->engagement_score = $engagementScore;

// timecreated(UNIX 타임스탬프, BIGINT) 칼럼
$data->timecreated = time();

// 필요에 따라 additional_info에 JSON 정보를 저장 가능
// $data->additional_info = json_encode(['browser' => $_SERVER['HTTP_USER_AGENT']]);

try {
    // Moodle에서 제공하는 insert_record() 사용
    // 'mdl_alt42_engagement_events'가 실제 테이블명(접두사 포함)이라고 가정
    $DB->insert_record('mdl_alt42_engagement_events', $data);

    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
