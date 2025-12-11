<?php
/**
 * Quantum Modeling 상태 기록 API
 * 
 * 양자 상태, 개념 활성화, 노드 이벤트 기록 기능 제공
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
        case 'saveQuantumState':
            saveQuantumState();
            break;
        case 'saveConceptActivation':
            saveConceptActivation();
            break;
        case 'saveNodeEvent':
            saveNodeEvent();
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
 * 양자 상태 기록
 */
function saveQuantumState() {
    global $DB, $USER;
    
    $sessionId = $_POST['sessionId'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $nodeId = $_POST['nodeId'] ?? null;
    $alpha = floatval($_POST['alpha'] ?? 0);
    $beta = floatval($_POST['beta'] ?? 0);
    $gamma = floatval($_POST['gamma'] ?? 0);
    $currentStage = intval($_POST['currentStage'] ?? 0);
    
    if (!$sessionId || !$contentId || !$nodeId) {
        throw new Exception('sessionId, contentId, and nodeId are required');
    }
    
    // 양자 상태 합 검증 (0.99 ~ 1.01 허용)
    $sum = $alpha + $beta + $gamma;
    if ($sum < 0.99 || $sum > 1.01) {
        // 정규화
        $total = $alpha + $beta + $gamma;
        if ($total > 0) {
            $alpha = $alpha / $total;
            $beta = $beta / $total;
            $gamma = $gamma / $total;
        }
    }
    
    $record = new stdClass();
    $record->session_id = $sessionId;
    $record->user_id = $USER->id;
    $record->content_id = $contentId;
    $record->node_id = $nodeId;
    $record->alpha = $alpha;
    $record->beta = $beta;
    $record->gamma = $gamma;
    $record->current_stage = $currentStage;
    
    $DB->insert_record('at_quantum_user_states', $record);
    
    echo json_encode([
        'success' => true,
        'message' => 'Quantum state saved successfully'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 개념 활성화 기록
 */
function saveConceptActivation() {
    global $DB, $USER;
    
    $sessionId = $_POST['sessionId'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $conceptId = $_POST['conceptId'] ?? null;
    $nodeId = $_POST['nodeId'] ?? null;
    
    if (!$sessionId || !$contentId || !$conceptId || !$nodeId) {
        throw new Exception('sessionId, contentId, conceptId, and nodeId are required');
    }
    
    // 중복 체크 (같은 세션에서 같은 개념은 한 번만 기록)
    $existing = $DB->get_record('at_quantum_user_concepts', [
        'session_id' => $sessionId,
        'concept_id' => $conceptId
    ]);
    
    if ($existing) {
        echo json_encode([
            'success' => true,
            'message' => 'Concept already activated in this session',
            'duplicate' => true
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $record = new stdClass();
    $record->session_id = $sessionId;
    $record->user_id = $USER->id;
    $record->content_id = $contentId;
    $record->concept_id = $conceptId;
    $record->node_id = $nodeId;
    
    $DB->insert_record('at_quantum_user_concepts', $record);
    
    echo json_encode([
        'success' => true,
        'message' => 'Concept activation saved successfully'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 노드 이벤트 기록
 */
function saveNodeEvent() {
    global $DB, $USER;
    
    $sessionId = $_POST['sessionId'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $eventType = $_POST['eventType'] ?? null; // click, backtrack, reset
    $nodeId = $_POST['nodeId'] ?? null;
    $stage = $_POST['stage'] ?? null;
    $pathBefore = $_POST['pathBefore'] ?? null;
    $pathAfter = $_POST['pathAfter'] ?? null;
    $conceptsBefore = $_POST['conceptsBefore'] ?? null;
    $conceptsAfter = $_POST['conceptsAfter'] ?? null;
    $quantumStateBefore = $_POST['quantumStateBefore'] ?? null;
    $quantumStateAfter = $_POST['quantumStateAfter'] ?? null;
    
    if (!$sessionId || !$contentId || !$eventType) {
        throw new Exception('sessionId, contentId, and eventType are required');
    }
    
    // 이벤트 타입 검증
    $validTypes = ['click', 'backtrack', 'reset'];
    if (!in_array($eventType, $validTypes)) {
        throw new Exception('Invalid eventType. Must be one of: ' . implode(', ', $validTypes));
    }
    
    $record = new stdClass();
    $record->session_id = $sessionId;
    $record->user_id = $USER->id;
    $record->content_id = $contentId;
    $record->event_type = $eventType;
    $record->node_id = $nodeId;
    $record->stage = $stage ? intval($stage) : null;
    $record->path_before = $pathBefore ? json_encode(json_decode($pathBefore)) : null;
    $record->path_after = $pathAfter ? json_encode(json_decode($pathAfter)) : null;
    $record->concepts_before = $conceptsBefore ? json_encode(json_decode($conceptsBefore)) : null;
    $record->concepts_after = $conceptsAfter ? json_encode(json_decode($conceptsAfter)) : null;
    $record->quantum_state_before = $quantumStateBefore ? json_encode(json_decode($quantumStateBefore)) : null;
    $record->quantum_state_after = $quantumStateAfter ? json_encode(json_decode($quantumStateAfter)) : null;
    
    $DB->insert_record('at_quantum_user_events', $record);
    
    echo json_encode([
        'success' => true,
        'message' => 'Node event saved successfully'
    ], JSON_UNESCAPED_UNICODE);
}

