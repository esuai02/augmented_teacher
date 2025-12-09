<?php
/**
 * AJAX endpoint for modifying spiral schedule
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
$changes = clean_param(optional_param('changes', '', PARAM_RAW), PARAM_TEXT);

// JSON 입력 처리
$jsonInput = file_get_contents('php://input');
if ($jsonInput) {
    $data = json_decode($jsonInput, true);
    if ($data) {
        $scheduleid = $data['schedule_id'] ?? $scheduleid;
        $changes = $data['changes'] ?? $changes;
    }
}

// 변경사항 파싱
if (is_string($changes)) {
    $payload = json_decode($changes, true);
} else {
    $payload = $changes;
}

if (!is_array($payload)) {
    $payload = [];
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
        'error' => 'Not authorized to modify this schedule'
    ]);
    exit;
}

try {
    $appliedCount = 0;
    $skippedCount = 0;
    
    // 각 변경사항 적용
    foreach ($payload as $change) {
        if (!isset($change['session_id']) || !isset($change['action'])) {
            $skippedCount++;
            continue;
        }
        
        $sessionId = (int)$change['session_id'];
        $action = $change['action'];
        
        // 세션 확인
        $session = $DB->get_record('spiral_sessions', [
            'id' => $sessionId,
            'schedule_id' => $scheduleid
        ]);
        
        if (!$session) {
            $skippedCount++;
            continue;
        }
        
        // 액션별 처리
        switch ($action) {
            case 'shift':
                // 시간 이동
                if (isset($change['new_time'])) {
                    $session->session_time = $change['new_time'];
                }
                break;
                
            case 'shrink':
                // 시간 단축
                if (isset($change['new_duration'])) {
                    $session->duration_minutes = (int)$change['new_duration'];
                }
                break;
                
            case 'move':
                // 날짜 이동
                if (isset($change['new_date'])) {
                    $newDate = strtotime($change['new_date']);
                    if ($newDate !== false) {
                        $session->session_date = $newDate;
                    }
                }
                break;
                
            case 'delete':
                // 세션 삭제
                $DB->delete_records('spiral_sessions', ['id' => $sessionId]);
                $appliedCount++;
                continue 2;
                
            case 'update':
                // 일반 업데이트
                foreach (['unit_id', 'unit_name', 'session_type', 'difficulty_level'] as $field) {
                    if (isset($change[$field])) {
                        $session->$field = $change[$field];
                    }
                }
                break;
                
            default:
                $skippedCount++;
                continue 2;
        }
        
        // 변경사항 저장
        $session->timemodified = time();
        $DB->update_record('spiral_sessions', $session);
        $appliedCount++;
    }
    
    // 스케줄 수정 시간 업데이트
    $schedule->timemodified = time();
    $DB->update_record('spiral_schedules', $schedule);
    
    // 응답
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'schedule_id' => $scheduleid,
        'applied' => $appliedCount,
        'skipped' => $skippedCount,
        'total' => count($payload),
        'message' => "Applied {$appliedCount} changes",
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Failed to modify schedule',
        'detail' => DEBUG_MODE ? $e->getMessage() : null
    ]);
    
    error_log('Spiral schedule modification error: ' . $e->getMessage());
}