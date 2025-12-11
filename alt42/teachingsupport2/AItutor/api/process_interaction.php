<?php
/**
 * 상호작용 처리 API
 * 
 * 프론트엔드에서 발생하는 모든 상호작용 이벤트 처리
 * - 필기 이벤트
 * - 제스처 이벤트
 * - 감정 선택
 * - 버튼 응답
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 */

header('Content-Type: application/json; charset=utf-8');

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(dirname(__DIR__) . '/services/interaction_engine.php');

$errorFile = __FILE__;

try {
    // 입력 데이터 파싱
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    // 필수 파라미터 확인
    $studentId = $input['student_id'] ?? $_GET['student_id'] ?? null;
    $contentId = $input['content_id'] ?? $_GET['content_id'] ?? null;
    
    if (!$studentId || !$contentId) {
        throw new Exception("student_id와 content_id가 필요합니다 [{$errorFile}:Line" . __LINE__ . "]");
    }
    
    // 이벤트 타입
    $eventType = $input['event_type'] ?? 'unknown';
    
    // 이벤트 데이터 구성
    $eventData = [
        'event_type' => $eventType,
        'timestamp' => time()
    ];
    
    // 이벤트 타입별 데이터 추가
    switch ($eventType) {
        case 'session_start':
            $eventData['unit_name'] = $input['unit_name'] ?? '수학';
            break;
            
        case 'writing_pause':
            $eventData['pause_duration'] = floatval($input['pause_duration'] ?? 0);
            $eventData['stroke_count'] = intval($input['stroke_count'] ?? 0);
            break;
            
        case 'writing_erase':
            $eventData['erase_count'] = intval($input['erase_count'] ?? 0);
            $eventData['erase_time_window'] = floatval($input['erase_time_window'] ?? 30);
            break;
            
        case 'gesture':
            $eventData['gesture_type'] = $input['gesture_type'] ?? '';
            break;
            
        case 'emotion':
            $eventData['emotion_type'] = $input['emotion_type'] ?? '';
            break;
            
        case 'user_response':
            $eventData['user_response'] = $input['value'] ?? '';
            $eventData['response_label'] = $input['label'] ?? '';
            $eventData['next_rule'] = $input['next_rule'] ?? '';
            break;
            
        case 'answer_submit':
            $eventData['answer'] = $input['answer'] ?? '';
            $eventData['answer_result'] = $input['answer_result'] ?? 'unknown';
            $eventData['error_type'] = $input['error_type'] ?? null;
            break;
            
        case 'step_complete':
            $eventData['step_number'] = intval($input['step_number'] ?? 0);
            $eventData['step_status'] = 'completed';
            break;
            
        case 'timeout':
            $eventData['timeout_rule'] = $input['next_rule'] ?? '';
            $eventData['user_response'] = 'thinking'; // 타임아웃은 기본적으로 사고중으로 처리
            break;
            
        case 'solve_complete':
            $eventData['solve_duration'] = floatval($input['solve_duration'] ?? 0);
            $eventData['item_difficulty'] = $input['item_difficulty'] ?? 'medium';
            break;
            
        default:
            // 추가 데이터 복사
            foreach ($input as $key => $value) {
                if (!in_array($key, ['student_id', 'content_id', 'event_type'])) {
                    $eventData[$key] = $value;
                }
            }
    }
    
    // next_rule이 있으면 user_response로 처리
    if (!empty($input['next_rule']) && empty($eventData['user_response'])) {
        // 룰에서 조건으로 사용할 수 있도록 설정
        $eventData['event_type'] = 'user_response';
        $eventData['user_response'] = $input['value'] ?? '';
    }
    
    // 상호작용 엔진 초기화 및 처리
    $engine = new InteractionEngine($studentId, $contentId);
    $response = $engine->processEvent($eventData);
    
    // 페르소나 정보 추가
    $persona = $engine->getCurrentPersona();
    if ($persona) {
        $response['persona'] = [
            'id' => $persona['id'],
            'name' => $persona['name'],
            'positive_name' => $persona['positive_name'],
            'icon' => $persona['icon'],
            'chat_style' => $persona['chat_style']
        ];
    }
    
    // 세션 상태 추가
    $response['session'] = $engine->getSessionState();
    
    echo json_encode([
        'success' => true,
        'data' => $response
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

