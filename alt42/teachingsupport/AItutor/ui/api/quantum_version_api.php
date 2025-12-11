<?php
/**
 * Quantum Modeling 버전 관리 API
 * 
 * 인지맵 버전 저장, 조회, 롤백 기능 제공
 * 
 * @package AugmentedTeacher\TeachingSupport\AItutor\UI\API
 * @version 1.0.0
 * @since 2025-12-11
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 직접 호출된 경우에만 헤더 설정
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Content-Type: application/json; charset=UTF-8');
}

$currentFile = __FILE__;

// Moodle 통합 (직접 호출 시에만)
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    try {
        if (file_exists("/home/moodle/public_html/moodle/config.php")) {
            include_once("/home/moodle/public_html/moodle/config.php");
            global $DB, $USER;
            require_login();
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Moodle config.php not found',
                'error_location' => "$currentFile:28"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Moodle 로드 실패: ' . $e->getMessage(),
            'error_location' => "$currentFile:35"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 직접 호출된 경우에만 액션 처리
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    try {
        switch ($action) {
            case 'saveVersion':
                saveVersion();
                break;
            case 'getVersionHistory':
                getVersionHistory();
                break;
            case 'getVersion':
                getVersion();
                break;
            case 'rollbackToVersion':
                rollbackToVersion();
                break;
            case 'compareVersions':
                compareVersions();
                break;
            default:
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid action: ' . $action
                ], JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'error_location' => "$currentFile:" . $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 버전 스냅샷 저장 (내부 호출용)
 */
function saveVersionSnapshot($contentId, $changeType = 'manual_edit', $suggestionId = null) {
    global $DB, $USER;
    
    // 다음 버전 번호 계산
    $maxVersion = $DB->get_field_sql(
        "SELECT MAX(version_number) FROM {at_quantum_map_versions} WHERE content_id = ?",
        [$contentId]
    );
    $versionNumber = ($maxVersion ?? 0) + 1;
    
    // 버전 ID 생성
    $versionId = 'VER_' . $contentId . '_' . $versionNumber . '_' . time();
    
    // 현재 노드 스냅샷
    $nodes = $DB->get_records('at_quantum_nodes', ['content_id' => $contentId, 'is_active' => 1]);
    $nodesSnapshot = array_values(array_map(function($n) {
        return (array)$n;
    }, $nodes));
    
    // 현재 엣지 스냅샷
    $edges = $DB->get_records('at_quantum_edges', ['content_id' => $contentId, 'is_active' => 1]);
    $edgesSnapshot = array_values(array_map(function($e) {
        return (array)$e;
    }, $edges));
    
    // 현재 개념 스냅샷
    $concepts = $DB->get_records('at_quantum_concepts', ['content_id' => $contentId, 'is_active' => 1]);
    $conceptsSnapshot = array_values(array_map(function($c) {
        return (array)$c;
    }, $concepts));
    
    // 노드-개념 연결
    $nodeConcepts = $DB->get_records('at_quantum_node_concepts', ['content_id' => $contentId]);
    $nodeConceptsSnapshot = array_values(array_map(function($nc) {
        return (array)$nc;
    }, $nodeConcepts));
    
    // 기존 current 버전 해제
    $DB->set_field('at_quantum_map_versions', 'is_current', 0, ['content_id' => $contentId, 'is_current' => 1]);
    
    // 변경 요약 생성
    $changeSummary = generateChangeSummary($changeType, $suggestionId);
    
    // 버전 레코드 생성
    $record = new stdClass();
    $record->version_id = $versionId;
    $record->content_id = $contentId;
    $record->version_number = $versionNumber;
    $record->change_type = $changeType;
    $record->suggestion_id = $suggestionId;
    $record->changed_by = $USER->id;
    $record->change_summary = $changeSummary;
    $record->nodes_snapshot = json_encode($nodesSnapshot, JSON_UNESCAPED_UNICODE);
    $record->edges_snapshot = json_encode($edgesSnapshot, JSON_UNESCAPED_UNICODE);
    $record->concepts_snapshot = json_encode([
        'concepts' => $conceptsSnapshot,
        'nodeConcepts' => $nodeConceptsSnapshot
    ], JSON_UNESCAPED_UNICODE);
    $record->is_current = 1;
    $record->created_at = date('Y-m-d H:i:s.u');
    
    $DB->insert_record('at_quantum_map_versions', $record);
    
    return $versionId;
}

/**
 * 변경 요약 생성
 */
function generateChangeSummary($changeType, $suggestionId = null) {
    global $DB;
    
    switch ($changeType) {
        case 'initial':
            return '초기 인지맵 생성';
        case 'ai_suggestion':
            if ($suggestionId) {
                $suggestion = $DB->get_record('at_quantum_ai_suggestions', ['suggestion_id' => $suggestionId]);
                if ($suggestion) {
                    return "AI 제안 반영: {$suggestion->title}";
                }
            }
            return 'AI 제안 반영';
        case 'manual_edit':
            return '수동 편집';
        case 'rollback':
            return '이전 버전으로 롤백';
        default:
            return $changeType;
    }
}

/**
 * 버전 저장 (외부 호출용)
 */
function saveVersion() {
    global $USER;
    
    $contentId = $_POST['contentId'] ?? null;
    $changeType = $_POST['changeType'] ?? 'manual_edit';
    $changeSummary = $_POST['changeSummary'] ?? null;
    
    if (!$contentId) {
        throw new Exception('contentId is required');
    }
    
    $versionId = saveVersionSnapshot($contentId, $changeType);
    
    echo json_encode([
        'success' => true,
        'versionId' => $versionId,
        'message' => '버전이 저장되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 버전 히스토리 조회
 */
function getVersionHistory() {
    global $DB;
    
    $contentId = $_GET['contentId'] ?? $_POST['contentId'] ?? null;
    $limit = intval($_GET['limit'] ?? $_POST['limit'] ?? 20);
    
    if (!$contentId) {
        throw new Exception('contentId is required');
    }
    
    $versions = $DB->get_records_sql(
        "SELECT v.*, u.firstname, u.lastname 
         FROM {at_quantum_map_versions} v
         LEFT JOIN {user} u ON v.changed_by = u.id
         WHERE v.content_id = ?
         ORDER BY v.version_number DESC
         LIMIT ?",
        [$contentId, $limit]
    );
    
    echo json_encode([
        'success' => true,
        'versions' => array_values(array_map(function($v) {
            return [
                'versionId' => $v->version_id,
                'versionNumber' => (int)$v->version_number,
                'changeType' => $v->change_type,
                'suggestionId' => $v->suggestion_id,
                'changeSummary' => $v->change_summary,
                'changedBy' => [
                    'id' => $v->changed_by,
                    'name' => trim(($v->firstname ?? '') . ' ' . ($v->lastname ?? ''))
                ],
                'isCurrent' => (bool)$v->is_current,
                'createdAt' => $v->created_at
            ];
        }, $versions))
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 특정 버전 조회
 */
function getVersion() {
    global $DB;
    
    $versionId = $_GET['versionId'] ?? $_POST['versionId'] ?? null;
    
    if (!$versionId) {
        throw new Exception('versionId is required');
    }
    
    $version = $DB->get_record('at_quantum_map_versions', ['version_id' => $versionId]);
    
    if (!$version) {
        throw new Exception('Version not found');
    }
    
    $conceptsData = json_decode($version->concepts_snapshot, true);
    
    echo json_encode([
        'success' => true,
        'version' => [
            'versionId' => $version->version_id,
            'versionNumber' => (int)$version->version_number,
            'contentId' => $version->content_id,
            'changeType' => $version->change_type,
            'changeSummary' => $version->change_summary,
            'isCurrent' => (bool)$version->is_current,
            'createdAt' => $version->created_at,
            'nodes' => json_decode($version->nodes_snapshot, true),
            'edges' => json_decode($version->edges_snapshot, true),
            'concepts' => $conceptsData['concepts'] ?? [],
            'nodeConcepts' => $conceptsData['nodeConcepts'] ?? []
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 이전 버전으로 롤백
 */
function rollbackToVersion() {
    global $DB, $USER;
    
    $versionId = $_POST['versionId'] ?? null;
    
    if (!$versionId) {
        throw new Exception('versionId is required');
    }
    
    // 롤백 대상 버전 조회
    $targetVersion = $DB->get_record('at_quantum_map_versions', ['version_id' => $versionId]);
    
    if (!$targetVersion) {
        throw new Exception('Version not found');
    }
    
    $contentId = $targetVersion->content_id;
    
    // 롤백 전 현재 상태 저장 (롤백 취소용)
    saveVersionSnapshot($contentId, 'rollback');
    
    // 기존 데이터 비활성화
    $DB->set_field('at_quantum_nodes', 'is_active', 0, ['content_id' => $contentId]);
    $DB->set_field('at_quantum_edges', 'is_active', 0, ['content_id' => $contentId]);
    $DB->set_field('at_quantum_concepts', 'is_active', 0, ['content_id' => $contentId]);
    
    // 노드-개념 연결 삭제
    $DB->delete_records('at_quantum_node_concepts', ['content_id' => $contentId]);
    
    // 스냅샷에서 데이터 복원
    $nodesSnapshot = json_decode($targetVersion->nodes_snapshot, true);
    $edgesSnapshot = json_decode($targetVersion->edges_snapshot, true);
    $conceptsData = json_decode($targetVersion->concepts_snapshot, true);
    
    // 노드 복원
    foreach ($nodesSnapshot as $nodeData) {
        // 기존 노드가 있으면 활성화, 없으면 생성
        $existing = $DB->get_record('at_quantum_nodes', [
            'node_id' => $nodeData['node_id'],
            'content_id' => $contentId
        ]);
        
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
            $record->order_index = $nodeData['order_index'] ?? 0;
            $record->is_active = 1;
            $record->created_at = date('Y-m-d H:i:s');
            
            $DB->insert_record('at_quantum_nodes', $record);
        }
    }
    
    // 엣지 복원
    foreach ($edgesSnapshot as $edgeData) {
        $existing = $DB->get_record('at_quantum_edges', [
            'source_node_id' => $edgeData['source_node_id'],
            'target_node_id' => $edgeData['target_node_id'],
            'content_id' => $contentId
        ]);
        
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
    
    // 개념 복원
    if (!empty($conceptsData['concepts'])) {
        foreach ($conceptsData['concepts'] as $conceptData) {
            $existing = $DB->get_record('at_quantum_concepts', [
                'concept_id' => $conceptData['concept_id'],
                'content_id' => $contentId
            ]);
            
            if ($existing) {
                $DB->set_field('at_quantum_concepts', 'is_active', 1, ['id' => $existing->id]);
            } else {
                $record = new stdClass();
                $record->concept_id = $conceptData['concept_id'];
                $record->content_id = $contentId;
                $record->name = $conceptData['name'];
                $record->icon = $conceptData['icon'] ?? '';
                $record->color = $conceptData['color'] ?? '';
                $record->order_index = $conceptData['order_index'] ?? 0;
                $record->is_active = 1;
                $record->created_at = date('Y-m-d H:i:s');
                
                $DB->insert_record('at_quantum_concepts', $record);
            }
        }
    }
    
    // 노드-개념 연결 복원
    if (!empty($conceptsData['nodeConcepts'])) {
        foreach ($conceptsData['nodeConcepts'] as $ncData) {
            $record = new stdClass();
            $record->node_id = $ncData['node_id'];
            $record->concept_id = $ncData['concept_id'];
            $record->content_id = $contentId;
            $record->order_index = $ncData['order_index'] ?? 0;
            $record->created_at = date('Y-m-d H:i:s');
            
            $DB->insert_record('at_quantum_node_concepts', $record);
        }
    }
    
    // 현재 버전 표시 업데이트
    $DB->set_field('at_quantum_map_versions', 'is_current', 0, ['content_id' => $contentId]);
    $DB->set_field('at_quantum_map_versions', 'is_current', 1, ['version_id' => $versionId]);
    
    echo json_encode([
        'success' => true,
        'message' => "버전 {$targetVersion->version_number}으로 롤백되었습니다.",
        'versionNumber' => (int)$targetVersion->version_number
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 버전 비교
 */
function compareVersions() {
    global $DB;
    
    $versionId1 = $_GET['versionId1'] ?? $_POST['versionId1'] ?? null;
    $versionId2 = $_GET['versionId2'] ?? $_POST['versionId2'] ?? null;
    
    if (!$versionId1 || !$versionId2) {
        throw new Exception('versionId1 and versionId2 are required');
    }
    
    $version1 = $DB->get_record('at_quantum_map_versions', ['version_id' => $versionId1]);
    $version2 = $DB->get_record('at_quantum_map_versions', ['version_id' => $versionId2]);
    
    if (!$version1 || !$version2) {
        throw new Exception('One or both versions not found');
    }
    
    $nodes1 = json_decode($version1->nodes_snapshot, true);
    $nodes2 = json_decode($version2->nodes_snapshot, true);
    $edges1 = json_decode($version1->edges_snapshot, true);
    $edges2 = json_decode($version2->edges_snapshot, true);
    
    // 노드 비교
    $nodeIds1 = array_column($nodes1, 'node_id');
    $nodeIds2 = array_column($nodes2, 'node_id');
    
    $addedNodes = array_diff($nodeIds2, $nodeIds1);
    $removedNodes = array_diff($nodeIds1, $nodeIds2);
    
    // 엣지 비교
    $edgeKeys1 = array_map(function($e) { 
        return $e['source_node_id'] . '->' . $e['target_node_id']; 
    }, $edges1);
    $edgeKeys2 = array_map(function($e) { 
        return $e['source_node_id'] . '->' . $e['target_node_id']; 
    }, $edges2);
    
    $addedEdges = array_diff($edgeKeys2, $edgeKeys1);
    $removedEdges = array_diff($edgeKeys1, $edgeKeys2);
    
    echo json_encode([
        'success' => true,
        'comparison' => [
            'version1' => [
                'versionId' => $version1->version_id,
                'versionNumber' => (int)$version1->version_number,
                'nodesCount' => count($nodes1),
                'edgesCount' => count($edges1)
            ],
            'version2' => [
                'versionId' => $version2->version_id,
                'versionNumber' => (int)$version2->version_number,
                'nodesCount' => count($nodes2),
                'edgesCount' => count($edges2)
            ],
            'changes' => [
                'addedNodes' => array_values($addedNodes),
                'removedNodes' => array_values($removedNodes),
                'addedEdges' => array_values($addedEdges),
                'removedEdges' => array_values($removedEdges)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
}


