<?php
/**
 * Hybrid State Stabilizer API
 * 하이브리드 상태 안정화 시스템 AJAX 엔드포인트
 *
 * @package AugmentedTeacher\Agent04\QuantumModeling\API
 * @version 1.0.0
 * @since 2025-12-06
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(dirname(__DIR__) . '/HybridStateStabilizer.php');
require_once(dirname(__DIR__) . '/HybridDataBridge.php');

header('Content-Type: application/json');

$currentFile = __FILE__;

try {
    // 요청 데이터 파싱
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
    $userId = $input['user_id'] ?? $_POST['user_id'] ?? $_GET['user_id'] ?? $USER->id;
    
    // 스태빌라이저 초기화
    $stabilizer = new HybridStateStabilizer(intval($userId));
    
    switch ($action) {
        // Fast Loop - 센서 데이터 기반 예측
        case 'fast_loop':
            $sensorData = $input['sensor_data'] ?? [];
            $result = $stabilizer->fastLoopPredict($sensorData);
            echo json_encode([
                'success' => true,
                'action' => 'fast_loop',
                'result' => $result,
                'state' => $stabilizer->getFullState()
            ]);
            break;
            
        // Kalman Correction - 이벤트 기반 보정
        case 'kalman_correction':
            $eventType = $input['event_type'] ?? 'page_view';
            $eventData = $input['event_data'] ?? [];
            $result = $stabilizer->kalmanCorrection($eventType, $eventData);
            echo json_encode([
                'success' => true,
                'action' => 'kalman_correction',
                'result' => $result,
                'state' => $stabilizer->getFullState()
            ]);
            break;
            
        // Active Ping - 능동 관측 발사
        case 'fire_ping':
            $level = intval($input['level'] ?? 1);
            $result = $stabilizer->firePing($level);
            echo json_encode([
                'success' => true,
                'action' => 'fire_ping',
                'result' => $result,
                'state' => $stabilizer->getFullState()
            ]);
            break;
            
        // Ping 반응 처리
        case 'ping_response':
            $pingId = $input['ping_id'] ?? '';
            $responded = $input['responded'] ?? false;
            $responseTime = floatval($input['response_time'] ?? 0);
            $responseContent = $input['response_content'] ?? null;
            
            $result = $stabilizer->processPingResponse($pingId, $responded, $responseTime, $responseContent);
            echo json_encode([
                'success' => true,
                'action' => 'ping_response',
                'result' => $result,
                'state' => $stabilizer->getFullState()
            ]);
            break;
            
        // 초기화
        case 'initialize':
            $options = $input['options'] ?? [];
            $result = $stabilizer->initializeState($options);
            echo json_encode([
                'success' => true,
                'action' => 'initialize',
                'result' => $result
            ]);
            break;
            
        // 현재 상태 조회
        case 'get_state':
            echo json_encode([
                'success' => true,
                'action' => 'get_state',
                'state' => $stabilizer->getFullState()
            ]);
            break;
            
        // 시뮬레이션 실행
        case 'run_simulation':
            $scenario = $input['scenario'] ?? [];
            $result = $stabilizer->runSimulation($scenario);
            echo json_encode([
                'success' => true,
                'action' => 'run_simulation',
                'result' => $result
            ]);
            break;
            
        // 이벤트 신호 목록 조회
        case 'get_event_signals':
            echo json_encode([
                'success' => true,
                'action' => 'get_event_signals',
                'event_signals' => HybridStateStabilizer::EVENT_SIGNALS,
                'ping_levels' => HybridStateStabilizer::PING_LEVELS
            ]);
            break;
            
        // 배치 이벤트 처리 (여러 이벤트 한 번에)
        case 'batch_events':
            $events = $input['events'] ?? [];
            $results = [];
            
            foreach ($events as $event) {
                if ($event['type'] === 'sensor') {
                    $results[] = $stabilizer->fastLoopPredict($event['data'] ?? []);
                } elseif ($event['type'] === 'event') {
                    $results[] = $stabilizer->kalmanCorrection(
                        $event['event_type'] ?? 'page_view',
                        $event['event_data'] ?? []
                    );
                }
            }
            
            echo json_encode([
                'success' => true,
                'action' => 'batch_events',
                'results' => $results,
                'state' => $stabilizer->getFullState()
            ]);
            break;
            
        // ============================================================
        // DataBridge 연동 액션
        // ============================================================
        
        // 학습 활동 이벤트 처리 (기존 Moodle 활동과 연동)
        case 'process_activity':
            $bridge = new HybridDataBridge(intval($userId));
            $activityType = $input['activity_type'] ?? 'page_view';
            $activityData = $input['activity_data'] ?? [];
            $result = $bridge->processActivityEvent($activityType, $activityData);
            echo json_encode([
                'success' => true,
                'action' => 'process_activity',
                'result' => $result
            ]);
            break;
            
        // 세션 초기화 (로그인/페이지 진입 시)
        case 'init_session':
            $bridge = new HybridDataBridge(intval($userId));
            $options = $input['options'] ?? [];
            $result = $bridge->initializeSession($options);
            echo json_encode([
                'success' => true,
                'action' => 'init_session',
                'result' => $result
            ]);
            break;
            
        // 최근 활동으로 동기화
        case 'sync_recent':
            $bridge = new HybridDataBridge(intval($userId));
            $lookbackMinutes = intval($input['lookback_minutes'] ?? 30);
            $result = $bridge->syncFromRecentActivity($lookbackMinutes);
            echo json_encode([
                'success' => true,
                'action' => 'sync_recent',
                'result' => $result
            ]);
            break;
            
        // 트래킹 데이터 처리
        case 'process_tracking':
            $bridge = new HybridDataBridge(intval($userId));
            $trackingData = $input['tracking_data'] ?? [];
            $result = $bridge->processTrackingData($trackingData);
            echo json_encode([
                'success' => true,
                'action' => 'process_tracking',
                'result' => $result
            ]);
            break;
            
        // 수동 이벤트 트리거 (테스트용)
        case 'trigger_event':
            require_once(dirname(__DIR__) . '/observers/HybridEventObserver.php');
            $eventType = $input['event_type'] ?? 'page_view';
            $eventData = $input['event_data'] ?? [];
            $result = HybridEventObserver::triggerManualEvent(intval($userId), $eventType, $eventData);
            echo json_encode([
                'success' => true,
                'action' => 'trigger_event',
                'result' => $result
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Unknown action: ' . $action,
                'file' => $currentFile,
                'line' => __LINE__
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $currentFile,
        'line' => $e->getLine()
    ]);
}

