<?php
/**
 * Quantum Modeling 히스토리 관리 API
 * 
 * 히스토리 스냅샷 저장 및 조회 기능 제공
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
        case 'saveHistorySnapshot':
            saveHistorySnapshot();
            break;
        case 'getHistorySnapshots':
            getHistorySnapshots();
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
 * 히스토리 스냅샷 저장
 */
function saveHistorySnapshot() {
    global $DB, $USER;
    
    $sessionId = $_POST['sessionId'] ?? null;
    $contentId = $_POST['contentId'] ?? null;
    $snapshotIndex = intval($_POST['snapshotIndex'] ?? 0);
    $path = $_POST['path'] ?? '[]';
    $quantumState = $_POST['quantumState'] ?? '{}';
    $activatedConcepts = $_POST['activatedConcepts'] ?? '[]';
    $currentStage = intval($_POST['currentStage'] ?? 0);
    $currentNodeId = $_POST['currentNodeId'] ?? null;
    $isComplete = intval($_POST['isComplete'] ?? 0);
    
    if (!$sessionId || !$contentId) {
        throw new Exception('sessionId and contentId are required');
    }
    
    // 기존 스냅샷 확인
    $existing = $DB->get_record('at_quantum_user_history', [
        'session_id' => $sessionId,
        'snapshot_index' => $snapshotIndex
    ]);
    
    $record = new stdClass();
    $record->session_id = $sessionId;
    $record->user_id = $USER->id;
    $record->content_id = $contentId;
    $record->snapshot_index = $snapshotIndex;
    $record->path = $path;
    $record->quantum_state = $quantumState;
    $record->activated_concepts = $activatedConcepts;
    $record->current_stage = $currentStage;
    $record->current_node_id = $currentNodeId;
    $record->is_complete = $isComplete;
    
    if ($existing) {
        $record->id = $existing->id;
        $DB->update_record('at_quantum_user_history', $record);
    } else {
        $DB->insert_record('at_quantum_user_history', $record);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'History snapshot saved successfully'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 히스토리 스냅샷 조회
 */
function getHistorySnapshots() {
    global $DB, $USER;
    
    $sessionId = $_GET['sessionId'] ?? $_POST['sessionId'] ?? null;
    
    if (!$sessionId) {
        throw new Exception('sessionId is required');
    }
    
    $snapshots = $DB->get_records('at_quantum_user_history', [
        'session_id' => $sessionId,
        'user_id' => $USER->id
    ], 'snapshot_index ASC');
    
    $result = [];
    foreach ($snapshots as $snapshot) {
        $result[] = [
            'snapshotIndex' => intval($snapshot->snapshot_index),
            'path' => json_decode($snapshot->path, true),
            'quantumState' => json_decode($snapshot->quantum_state, true),
            'activatedConcepts' => json_decode($snapshot->activated_concepts, true),
            'currentStage' => intval($snapshot->current_stage),
            'currentNodeId' => $snapshot->current_node_id,
            'isComplete' => (bool)$snapshot->is_complete
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);
}

