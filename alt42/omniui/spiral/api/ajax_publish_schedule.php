<?php
/**
 * AJAX endpoint for publishing spiral schedule
 * 
 * @package    OmniUI
 * @subpackage spiral/api
 * @copyright  2024 MathKing
 */

// Moodle 환경 로드
require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../config/spiral_config.php');

use local_spiral\api\plan_api;

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
$scheduleid = clean_param(required_param('schedule_id', PARAM_INT), PARAM_INT);
$notify = clean_param(optional_param('notify', true, PARAM_BOOL), PARAM_BOOL);

// JSON 입력 처리
$jsonInput = file_get_contents('php://input');
if ($jsonInput) {
    $data = json_decode($jsonInput, true);
    if ($data) {
        $scheduleid = $data['schedule_id'] ?? $scheduleid;
        $notify = $data['notify'] ?? $notify;
    }
}

// 스케줄 존재 확인
$schedule = $DB->get_record('spiral_schedules', ['id' => $scheduleid]);

if (!$schedule) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => 'Schedule not found'
    ]);
    exit;
}

// 소유권 확인
if ($schedule->teacher_id != $USER->id && !is_siteadmin()) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Not authorized to publish this schedule'
    ]);
    exit;
}

// 이미 발행된 상태 확인
if ($schedule->status === 'published' || $schedule->status === 'active') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'schedule_id' => $scheduleid,
        'status' => $schedule->status,
        'message' => 'Schedule already published',
        'timestamp' => time()
    ]);
    exit;
}

try {
    // 상태 변경
    $schedule->status = 'published';
    $schedule->timemodified = time();
    $DB->update_record('spiral_schedules', $schedule);
    
    // 알림 발송
    if ($notify) {
        $student = $DB->get_record('user', ['id' => $schedule->student_id]);
        $teacher = $DB->get_record('user', ['id' => $schedule->teacher_id]);
        
        if ($student && $teacher) {
            // 메시지 생성
            $message = new \core\message\message();
            $message->component = 'local_spiral';
            $message->name = 'schedule_published';
            $message->userfrom = $teacher;
            $message->userto = $student;
            
            $message->subject = '새로운 학습 스케줄이 발행되었습니다';
            $message->fullmessage = sprintf(
                '안녕하세요 %s님,\n\n' .
                '%s 선생님이 새로운 학습 스케줄을 발행하였습니다.\n' .
                '시작일: %s\n' .
                '종료일: %s\n\n' .
                '자세한 내용은 학습 대시보드에서 확인하세요.',
                $student->firstname,
                $teacher->firstname . ' ' . $teacher->lastname,
                date('Y-m-d', $schedule->start_date),
                date('Y-m-d', $schedule->end_date)
            );
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = '';
            $message->smallmessage = '새로운 학습 스케줄이 발행되었습니다';
            $message->notification = 1;
            
            // 메시지 발송
            message_send($message);
            
            // 부모 알림 (phone2 필드 활용)
            if (!empty($student->phone2)) {
                // TODO: SMS 알림 구현
                // 외부 SMS API 연동 필요
            }
        }
    }
    
    // 로그 기록
    $eventdata = [
        'context' => context_system::instance(),
        'objectid' => $scheduleid,
        'relateduserid' => $schedule->student_id,
        'other' => [
            'teacher_id' => $schedule->teacher_id,
            'status' => 'published'
        ]
    ];
    
    // 이벤트 트리거
    // Note: local_spiral\event\schedule_published 클래스 정의 필요
    
    // 응답
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'schedule_id' => $scheduleid,
        'status' => 'published',
        'notified' => $notify,
        'message' => 'Schedule published successfully',
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Failed to publish schedule',
        'detail' => DEBUG_MODE ? $e->getMessage() : null
    ]);
    
    error_log('Spiral schedule publish error: ' . $e->getMessage());
}