<?php
/**
 * Quantum Modeling 버전 관리 API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Content-Type: application/json; charset=UTF-8');
    
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
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    try {
        switch ($action) {
            case 'saveVersion': saveVersion(); break;
            case 'getVersionHistory': getVersionHistory(); break;
            case 'rollbackToVersion': rollbackToVersion(); break;
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function saveVersionSnapshot($contentId, $changeType = 'manual_edit', $suggestionId = null) {
    global $DB, $USER;
    
    try {
        // 버전 테이블 존재 확인
        $maxVersion = $DB->get_field_sql("SELECT MAX(version_number) FROM {at_quantum_map_versions} WHERE content_id = ?", [$contentId]);
        $versionNumber = ($maxVersion ?? 0) + 1;
    } catch (Exception $e) {
        // 테이블이 없으면 버전 저장 건너뜀
        error_log("[quantum_version_api] 버전 테이블 접근 실패: " . $e->getMessage());
        return null;
    }
    
    $versionId = 'VER_' . substr(md5($contentId), 0, 8) . '_' . $versionNumber . '_' . time();
    
    // 노드/엣지/개념 조회 (테이블 없으면 빈 배열)
    try {
        $nodes = $DB->get_records('at_quantum_nodes', ['content_id' => $contentId, 'is_active' => 1]) ?: [];
    } catch (Exception $e) {
        $nodes = [];
    }
    
    try {
        $edges = $DB->get_records('at_quantum_edges', ['content_id' => $contentId, 'is_active' => 1]) ?: [];
    } catch (Exception $e) {
        $edges = [];
    }
    
    try {
        $concepts = $DB->get_records('at_quantum_concepts', ['content_id' => $contentId, 'is_active' => 1]) ?: [];
    } catch (Exception $e) {
        $concepts = [];
    }
    
    try {
        $DB->set_field('at_quantum_map_versions', 'is_current', 0, ['content_id' => $contentId]);
    } catch (Exception $e) {
        // 무시
    }
    
    $record = new stdClass();
    $record->version_id = $versionId;
    $record->content_id = $contentId;
    $record->version_number = $versionNumber;
    $record->change_type = $changeType;
    $record->suggestion_id = $suggestionId;
    $record->changed_by = $USER->id ?? 0;
    $record->change_summary = $changeType === 'ai_suggestion' ? 'AI 제안 반영' : ($changeType === 'rollback' ? '이전 버전으로 롤백' : '수동 편집');
    $record->nodes_snapshot = json_encode(array_values(array_map(function($n) { return (array)$n; }, $nodes)), JSON_UNESCAPED_UNICODE);
    $record->edges_snapshot = json_encode(array_values(array_map(function($e) { return (array)$e; }, $edges)), JSON_UNESCAPED_UNICODE);
    $record->concepts_snapshot = json_encode(['concepts' => array_values(array_map(function($c) { return (array)$c; }, $concepts))], JSON_UNESCAPED_UNICODE);
    $record->is_current = 1;
    $record->created_at = date('Y-m-d H:i:s');
    
    try {
        $DB->insert_record('at_quantum_map_versions', $record);
        return $versionId;
    } catch (Exception $e) {
        error_log("[quantum_version_api] 버전 저장 실패: " . $e->getMessage());
        return null;
    }
}

function saveVersion() {
    $contentId = $_POST['contentId'] ?? null;
    if (!$contentId) throw new Exception('contentId is required');
    
    $versionId = saveVersionSnapshot($contentId, 'manual_edit');
    echo json_encode(['success' => true, 'versionId' => $versionId], JSON_UNESCAPED_UNICODE);
}

function getVersionHistory() {
    global $DB;
    
    $contentId = $_GET['contentId'] ?? $_POST['contentId'] ?? null;
    if (!$contentId) throw new Exception('contentId is required');
    
    $versions = $DB->get_records_sql(
        "SELECT v.*, u.firstname, u.lastname FROM {at_quantum_map_versions} v LEFT JOIN {user} u ON v.changed_by = u.id WHERE v.content_id = ? ORDER BY v.version_number DESC LIMIT 20",
        [$contentId]
    );
    
    echo json_encode([
        'success' => true,
        'versions' => array_values(array_map(function($v) {
            return [
                'versionId' => $v->version_id,
                'versionNumber' => (int)$v->version_number,
                'changeType' => $v->change_type,
                'changeSummary' => $v->change_summary,
                'changedBy' => ['name' => trim(($v->firstname ?? '') . ' ' . ($v->lastname ?? ''))],
                'isCurrent' => (bool)$v->is_current,
                'createdAt' => $v->created_at
            ];
        }, $versions))
    ], JSON_UNESCAPED_UNICODE);
}

function rollbackToVersion() {
    global $DB, $USER;
    
    $versionId = $_POST['versionId'] ?? null;
    if (!$versionId) throw new Exception('versionId is required');
    
    $targetVersion = $DB->get_record('at_quantum_map_versions', ['version_id' => $versionId]);
    if (!$targetVersion) throw new Exception('Version not found');
    
    $contentId = $targetVersion->content_id;
    
    saveVersionSnapshot($contentId, 'rollback');
    
    $DB->set_field('at_quantum_nodes', 'is_active', 0, ['content_id' => $contentId]);
    $DB->set_field('at_quantum_edges', 'is_active', 0, ['content_id' => $contentId]);
    
    $nodesSnapshot = json_decode($targetVersion->nodes_snapshot, true);
    $edgesSnapshot = json_decode($targetVersion->edges_snapshot, true);
    
    foreach ($nodesSnapshot as $nodeData) {
        $existing = $DB->get_record('at_quantum_nodes', ['node_id' => $nodeData['node_id'], 'content_id' => $contentId]);
        if ($existing) {
            $DB->set_field('at_quantum_nodes', 'is_active', 1, ['id' => $existing->id]);
        } else {
            $record = new stdClass();
            $record->node_id = $nodeData['node_id'];
            $record->content_id = $contentId;
            $record->label = $nodeData['label'];
            $record->type = $nodeData['type'];
            $record->stage = $nodeData['stage'];
            $record->x = $nodeData['x'];
            $record->y = $nodeData['y'];
            $record->description = $nodeData['description'] ?? '';
            $record->is_active = 1;
            $record->created_at = date('Y-m-d H:i:s');
            $DB->insert_record('at_quantum_nodes', $record);
        }
    }
    
    foreach ($edgesSnapshot as $edgeData) {
        $existing = $DB->get_record('at_quantum_edges', ['source_node_id' => $edgeData['source_node_id'], 'target_node_id' => $edgeData['target_node_id'], 'content_id' => $contentId]);
        if ($existing) {
            $DB->set_field('at_quantum_edges', 'is_active', 1, ['id' => $existing->id]);
        } else {
            $record = new stdClass();
            $record->source_node_id = $edgeData['source_node_id'];
            $record->target_node_id = $edgeData['target_node_id'];
            $record->content_id = $contentId;
            $record->is_active = 1;
            $record->created_at = date('Y-m-d H:i:s');
            $DB->insert_record('at_quantum_edges', $record);
        }
    }
    
    $DB->set_field('at_quantum_map_versions', 'is_current', 0, ['content_id' => $contentId]);
    $DB->set_field('at_quantum_map_versions', 'is_current', 1, ['version_id' => $versionId]);
    
    echo json_encode(['success' => true, 'message' => "버전 {$targetVersion->version_number}으로 롤백되었습니다."], JSON_UNESCAPED_UNICODE);
}

