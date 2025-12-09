<?php
/**
 * AJAX endpoint for generating spiral schedule
 * 
 * @package    OmniUI
 * @subpackage spiral/api
 * @copyright  2024 MathKing
 */

// Moodle 환경 로드
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../config/spiral_config.php');

// 클래스 로드
use local_spiral\api\plan_api;
use local_spiral\service\scheduler_bridge;

// CSRF 보호
require_sesskey();

// 권한 체크
try {
    plan_api::require_teacher_capability();
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Permission denied'
    ]);
    exit;
}

// 파라미터 수집 - 보안 강화
$courseid = optional_param('courseid', 0, PARAM_INT);
$teacherid = $USER->id; // 항상 현재 사용자 ID 사용
$studentid = required_param('studentid', PARAM_INT);
$start = clean_param(required_param('start_date', PARAM_RAW_TRIMMED), PARAM_TEXT);
$end = clean_param(required_param('end_date', PARAM_RAW_TRIMMED), PARAM_TEXT);
$hours = optional_param('hours_per_week', 0, PARAM_INT);
$alpha = optional_param('alpha', 0.7, PARAM_FLOAT);
$beta = optional_param('beta', 0.3, PARAM_FLOAT);
$examid = optional_param('exam_id', 0, PARAM_INT);

// JSON 입력 처리
$jsonInput = file_get_contents('php://input');
if ($jsonInput) {
    $data = json_decode($jsonInput, true);
    if ($data) {
        $courseid = clean_param($data['courseid'] ?? $courseid, PARAM_INT);
        // $teacherid는 항상 $USER->id 사용
        $studentid = clean_param($data['studentid'] ?? $studentid, PARAM_INT);
        $start = clean_param($data['start_date'] ?? $start, PARAM_TEXT);
        $end = clean_param($data['end_date'] ?? $end, PARAM_TEXT);
        $hours = clean_param($data['hours_per_week'] ?? $hours, PARAM_INT);
        $alpha = clean_param($data['alpha'] ?? $alpha, PARAM_FLOAT);
        $beta = clean_param($data['beta'] ?? $beta, PARAM_FLOAT);
        $examid = clean_param($data['exam_id'] ?? $examid, PARAM_INT);
    }
}

// 유효성 검사
if (empty($studentid) || empty($start) || empty($end)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Missing required parameters'
    ]);
    exit;
}

// 날짜 유효성
$startTime = strtotime($start);
$endTime = strtotime($end);

if ($startTime === false || $endTime === false || $startTime >= $endTime) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid date range'
    ]);
    exit;
}

// 비율 검증
if ($alpha + $beta != 1.0) {
    $total = $alpha + $beta;
    if ($total > 0) {
        $alpha = $alpha / $total;
        $beta = $beta / $total;
    } else {
        $alpha = 0.7;
        $beta = 0.3;
    }
}

try {
    // 규칙 및 설정 로드
    $rules = include(__DIR__ . '/../config/algorithm_rules.php');
    $cfg = [];
    
    // 스케줄러 브릿지 생성
    $bridge = new scheduler_bridge($DB, $rules, $cfg);
    
    // 스케줄 생성
    $result = $bridge->generate([
        'courseid' => $courseid,
        'teacherid' => $teacherid,
        'studentid' => $studentid,
        'exam_id' => $examid,
        'start_date' => $start,
        'end_date' => $end,
        'hours_per_week' => $hours,
        'alpha' => $alpha,
        'beta' => $beta
    ]);
    
    // 성공 응답
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'schedule_id' => $result['schedule_id'] ?? 0,
        'summary' => $result['summary'] ?? [],
        'conflicts' => $result['conflicts'] ?? [],
        'message' => 'Schedule generated successfully',
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 에러 응답
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Failed to generate schedule',
        'detail' => DEBUG_MODE ? $e->getMessage() : null
    ]);
    
    error_log('Spiral schedule generation error: ' . $e->getMessage());
}