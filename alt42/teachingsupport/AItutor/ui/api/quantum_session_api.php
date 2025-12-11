<?php
/**
 * Quantum Modeling 세션 관리 API
 * 
 * 세션 저장, 로드, 조회 기능 제공
 * 
 * @package AugmentedTeacher\TeachingSupport\AItutor\UI\API
 * @version 1.0.0
 * @since 2025-12-11
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

$currentFile = __FILE__;

// Moodle 통합
try {
    if (file_exists("/home/moodle/public_html/moodle/config.php")) {
        include_once("/home/moodle/public_html/moodle/config.php");
        global $DB, $USER;
        require_login();
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Moodle config.php not found',
            'error_location' => "$currentFile:26"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Moodle 로드 실패: ' . $e->getMessage(),
        'error_location' => "$currentFile:33"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'saveSession':
            saveSession();
            break;
        case 'loadSession':
            loadSession();
            break;
        case 'getLastSession':
            getLastSession();
            break;
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_location' => "$currentFile:" . $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 세션 저장
 */
function saveSession() {
    global $DB, $USER;
    
    $sessionId = $_POST['sessionId'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $currentStage = intval($_POST['currentStage'] ?? 0);
    $currentNodeId = $_POST['currentNodeId'] ?? null;
    $isComplete = intval($_POST['isComplete'] ?? 0);
    $selectedPath = $_POST['selectedPath'] ?? '[]';
    $activatedConcepts = $_POST['activatedConcepts'] ?? '[]';
    $quantumState = $_POST['quantumState'] ?? '{}';
    $historySnapshot = $_POST['historySnapshot'] ?? '[]';
    $finalResult = $_POST['finalResult'] ?? null;
    
    if (!$sessionId || !$contentId) {
        throw new Exception('sessionId and contentId are required');
    }
    
    // 기존 세션 확인
    $existing = $DB->get_record('at_quantum_user_sessions', ['session_id' => $sessionId]);
    
    $record = new stdClass();
    $record->session_id = $sessionId;
    $record->user_id = $USER->id;
    $record->content_id = $contentId;
    $record->current_stage = $currentStage;
    $record->current_node_id = $currentNodeId;
    $record->is_complete = $isComplete;
    $record->selected_path = $selectedPath;
    $record->activated_concepts = $activatedConcepts;
    $record->quantum_state = $quantumState;
    $record->history_snapshot = $historySnapshot;
    $record->final_result = $finalResult;
    $record->updated_at = date('Y-m-d H:i:s');
    
    if ($existing) {
        $record->id = $existing->id;
        if ($isComplete && !$existing->completed_at) {
            $record->completed_at = date('Y-m-d H:i:s');
        }
        $DB->update_record('at_quantum_user_sessions', $record);
        $sessionId = $record->session_id;
    } else {
        $record->started_at = date('Y-m-d H:i:s');
        $sessionId = $DB->insert_record('at_quantum_user_sessions', $record);
    }
    
    echo json_encode([
        'success' => true,
        'sessionId' => $sessionId,
        'message' => 'Session saved successfully'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 세션 로드
 */
function loadSession() {
    global $DB, $USER;
    
    $sessionId = $_GET['sessionId'] ?? $_POST['sessionId'] ?? null;
    
    if (!$sessionId) {
        throw new Exception('sessionId is required');
    }
    
    $session = $DB->get_record('at_quantum_user_sessions', [
        'session_id' => $sessionId,
        'user_id' => $USER->id
    ]);
    
    if (!$session) {
        throw new Exception('Session not found');
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'sessionId' => $session->session_id,
            'contentId' => $session->content_id,
            'currentStage' => intval($session->current_stage),
            'currentNodeId' => $session->current_node_id,
            'isComplete' => (bool)$session->is_complete,
            'selectedPath' => json_decode($session->selected_path, true),
            'activatedConcepts' => json_decode($session->activated_concepts, true),
            'quantumState' => json_decode($session->quantum_state, true),
            'historySnapshot' => json_decode($session->history_snapshot, true),
            'finalResult' => $session->final_result
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 마지막 세션 조회 (자동 복원용)
 */
function getLastSession() {
    global $DB, $USER;
    
    $contentId = $_GET['contentId'] ?? $_POST['contentId'] ?? null;
    
    if (!$contentId) {
        throw new Exception('contentId is required');
    }
    
    $session = $DB->get_record_sql(
        "SELECT * FROM {at_quantum_user_sessions} 
         WHERE user_id = ? AND content_id = ? AND is_complete = 0 
         ORDER BY updated_at DESC LIMIT 1",
        [$USER->id, $contentId]
    );
    
    if (!$session) {
        echo json_encode([
            'success' => true,
            'hasSession' => false,
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'hasSession' => true,
        'data' => [
            'sessionId' => $session->session_id,
            'contentId' => $session->content_id,
            'currentStage' => intval($session->current_stage),
            'currentNodeId' => $session->current_node_id,
            'isComplete' => (bool)$session->is_complete,
            'selectedPath' => json_decode($session->selected_path, true),
            'activatedConcepts' => json_decode($session->activated_concepts, true),
            'quantumState' => json_decode($session->quantum_state, true),
            'historySnapshot' => json_decode($session->history_snapshot, true),
            'finalResult' => $session->final_result
        ]
    ], JSON_UNESCAPED_UNICODE);
}

