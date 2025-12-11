<?php
/**
 * Quantum Modeling 세션 관리 API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

$currentFile = __FILE__;

try {
    if (file_exists("/home/moodle/public_html/moodle/config.php")) {
        include_once("/home/moodle/public_html/moodle/config.php");
        global $DB, $USER;
        require_login();
    } else {
        echo json_encode(['success' => false, 'error' => 'Moodle config.php not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Moodle 로드 실패'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'saveSession': saveSession(); break;
        case 'loadSession': loadSession(); break;
        case 'getLastSession': getLastSession(); break;
        case 'updateNodePosition': updateNodePosition(); break;
        case 'saveNodeToDb': saveNodeToDb(); break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function saveSession() {
    global $DB, $USER;
    
    $sessionId = $_POST['sessionId'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    
    if (!$sessionId || !$contentId) throw new Exception('sessionId and contentId are required');
    
    $existing = $DB->get_record('at_quantum_user_sessions', ['session_id' => $sessionId]);
    
    $record = new stdClass();
    $record->session_id = $sessionId;
    $record->user_id = $USER->id;
    $record->content_id = $contentId;
    $record->current_stage = intval($_POST['currentStage'] ?? 0);
    $record->current_node_id = $_POST['currentNodeId'] ?? null;
    $record->is_complete = intval($_POST['isComplete'] ?? 0);
    $record->selected_path = $_POST['selectedPath'] ?? '[]';
    $record->activated_concepts = $_POST['activatedConcepts'] ?? '[]';
    $record->quantum_state = $_POST['quantumState'] ?? '{}';
    $record->history_snapshot = $_POST['historySnapshot'] ?? '[]';
    $record->final_result = $_POST['finalResult'] ?? null;
    $record->updated_at = date('Y-m-d H:i:s');
    
    if ($existing) {
        $record->id = $existing->id;
        if ($record->is_complete && !$existing->completed_at) {
            $record->completed_at = date('Y-m-d H:i:s');
        }
        $DB->update_record('at_quantum_user_sessions', $record);
    } else {
        $record->started_at = date('Y-m-d H:i:s');
        $DB->insert_record('at_quantum_user_sessions', $record);
    }
    
    echo json_encode(['success' => true, 'sessionId' => $sessionId], JSON_UNESCAPED_UNICODE);
}

function loadSession() {
    global $DB, $USER;
    
    $sessionId = $_GET['sessionId'] ?? $_POST['sessionId'] ?? null;
    if (!$sessionId) throw new Exception('sessionId is required');
    
    $session = $DB->get_record('at_quantum_user_sessions', ['session_id' => $sessionId, 'user_id' => $USER->id]);
    if (!$session) throw new Exception('Session not found');
    
    echo json_encode([
        'success' => true,
        'data' => [
            'sessionId' => $session->session_id,
            'contentId' => $session->content_id,
            'currentStage' => (int)$session->current_stage,
            'isComplete' => (bool)$session->is_complete,
            'selectedPath' => json_decode($session->selected_path, true),
            'activatedConcepts' => json_decode($session->activated_concepts, true),
            'quantumState' => json_decode($session->quantum_state, true),
            'historySnapshot' => json_decode($session->history_snapshot, true)
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function getLastSession() {
    global $DB, $USER;
    
    $contentId = $_GET['contentId'] ?? $_POST['contentId'] ?? null;
    if (!$contentId) throw new Exception('contentId is required');
    
    $session = $DB->get_record_sql(
        "SELECT * FROM {at_quantum_user_sessions} WHERE user_id = ? AND content_id = ? AND is_complete = 0 ORDER BY updated_at DESC LIMIT 1",
        [$USER->id, $contentId]
    );
    
    if (!$session) {
        echo json_encode(['success' => true, 'hasSession' => false, 'data' => null], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'hasSession' => true,
        'data' => [
            'sessionId' => $session->session_id,
            'contentId' => $session->content_id,
            'currentStage' => (int)$session->current_stage,
            'isComplete' => (bool)$session->is_complete,
            'selectedPath' => json_decode($session->selected_path, true),
            'activatedConcepts' => json_decode($session->activated_concepts, true),
            'quantumState' => json_decode($session->quantum_state, true),
            'historySnapshot' => json_decode($session->history_snapshot, true)
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 노드 위치 업데이트 (DB에 이미 있는 노드)
 */
function updateNodePosition() {
    global $DB;
    
    $nodeId = $_POST['nodeId'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $x = $_POST['x'] ?? null;
    $y = $_POST['y'] ?? null;
    
    if (!$nodeId || !$contentId || $x === null || $y === null) {
        throw new Exception('nodeId, contentId, x, y are required');
    }
    
    // 기존 노드 확인
    $existing = $DB->get_record('at_quantum_nodes', [
        'node_id' => $nodeId,
        'content_id' => $contentId
    ]);
    
    if ($existing) {
        // 기존 노드 위치 업데이트
        $existing->x = (int)$x;
        $existing->y = (int)$y;
        $existing->updated_at = date('Y-m-d H:i:s');
        $DB->update_record('at_quantum_nodes', $existing);
        
        echo json_encode([
            'success' => true,
            'message' => '노드 위치가 업데이트되었습니다.',
            'nodeId' => $nodeId,
            'x' => (int)$x,
            'y' => (int)$y
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // 노드가 DB에 없으면 새로 생성해야 함
        echo json_encode([
            'success' => false,
            'error' => '노드가 DB에 없습니다. saveNodeToDb를 먼저 호출하세요.',
            'needsSave' => true
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 하드코딩된 노드를 DB에 저장 (위치 포함)
 */
function saveNodeToDb() {
    global $DB;
    
    $nodeId = $_POST['nodeId'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $x = $_POST['x'] ?? null;
    $y = $_POST['y'] ?? null;
    $label = $_POST['label'] ?? '';
    $type = $_POST['type'] ?? 'correct';
    $stage = $_POST['stage'] ?? 0;
    $description = $_POST['description'] ?? '';
    
    if (!$nodeId || !$contentId || $x === null || $y === null) {
        throw new Exception('nodeId, contentId, x, y are required');
    }
    
    // 기존 노드 확인
    $existing = $DB->get_record('at_quantum_nodes', [
        'node_id' => $nodeId,
        'content_id' => $contentId
    ]);
    
    if ($existing) {
        // 업데이트
        $existing->x = (int)$x;
        $existing->y = (int)$y;
        $existing->label = $label ?: $existing->label;
        $existing->type = $type ?: $existing->type;
        $existing->stage = (int)$stage;
        $existing->description = $description ?: $existing->description;
        $existing->updated_at = date('Y-m-d H:i:s');
        $DB->update_record('at_quantum_nodes', $existing);
    } else {
        // 새로 삽입
        $record = new stdClass();
        $record->node_id = $nodeId;
        $record->content_id = $contentId;
        $record->label = $label;
        $record->type = $type;
        $record->stage = (int)$stage;
        $record->x = (int)$x;
        $record->y = (int)$y;
        $record->description = $description;
        $record->is_active = 1;
        $record->created_at = date('Y-m-d H:i:s');
        $DB->insert_record('at_quantum_nodes', $record);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '노드가 DB에 저장되었습니다.',
        'nodeId' => $nodeId,
        'x' => (int)$x,
        'y' => (int)$y
    ], JSON_UNESCAPED_UNICODE);
}

