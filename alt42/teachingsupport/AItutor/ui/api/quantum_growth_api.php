<?php
/**
 * Quantum Modeling AI 성장 API
 * 
 * AI 요청 생성, 제안 조회, 승인/거절 기능 제공
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
        case 'createRequest':
            createRequest();
            break;
        case 'getSuggestion':
            getSuggestion();
            break;
        case 'getPendingSuggestions':
            getPendingSuggestions();
            break;
        case 'approveSuggestion':
            approveSuggestion();
            break;
        case 'rejectSuggestion':
            rejectSuggestion();
            break;
        case 'getContentData':
            getContentData();
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

/**
 * 사용자 역할 가져오기
 */
function getUserRole() {
    global $DB, $USER;
    
    $userrole = $DB->get_record_sql(
        "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = '22'",
        [$USER->id]
    );
    
    return $userrole ? $userrole->data : 'student';
}

/**
 * 권한 검증 (본인 + 선생님)
 */
function canManageSuggestion($suggestionUserId) {
    global $USER;
    
    $role = getUserRole();
    
    // 선생님은 모든 제안 관리 가능
    if ($role === 'teacher' || $role === 'admin') {
        return true;
    }
    
    // 본인이 생성한 제안만 관리 가능
    return $USER->id == $suggestionUserId;
}

/**
 * AI 요청 생성
 */
function createRequest() {
    global $DB, $USER, $currentFile;
    
    $contentId = $_POST['contentId'] ?? null;
    $requestType = $_POST['requestType'] ?? null; // new_solution, misconception, custom_input
    $userInput = $_POST['userInput'] ?? null; // custom_input인 경우
    
    if (!$contentId || !$requestType) {
        throw new Exception('contentId and requestType are required');
    }
    
    // 요청 타입 검증
    $validTypes = ['new_solution', 'misconception', 'custom_input'];
    if (!in_array($requestType, $validTypes)) {
        throw new Exception('Invalid requestType: ' . $requestType);
    }
    
    // custom_input인 경우 userInput 필수
    if ($requestType === 'custom_input' && empty($userInput)) {
        throw new Exception('userInput is required for custom_input request');
    }
    
    // 요청 ID 생성
    $requestId = 'REQ_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    
    // 현재 컨텐츠 정보 스냅샷
    $contextSnapshot = getContentSnapshot($contentId);
    $nodesSnapshot = getNodesSnapshot($contentId);
    
    // 요청 기록 생성
    $record = new stdClass();
    $record->request_id = $requestId;
    $record->user_id = $USER->id;
    $record->content_id = $contentId;
    $record->request_type = $requestType;
    $record->status = 'pending';
    $record->user_input = $userInput;
    $record->context_snapshot = json_encode($contextSnapshot, JSON_UNESCAPED_UNICODE);
    $record->existing_nodes_snapshot = json_encode($nodesSnapshot, JSON_UNESCAPED_UNICODE);
    $record->openai_model = 'gpt-4o';
    $record->created_at = date('Y-m-d H:i:s.u');
    
    $DB->insert_record('at_quantum_ai_requests', $record);
    
    // OpenAI API 호출
    $startTime = microtime(true);
    
    try {
        // 상태를 processing으로 변경
        $DB->set_field('at_quantum_ai_requests', 'status', 'processing', ['request_id' => $requestId]);
        
        // OpenAI 프록시 호출
        $openaiResult = callOpenAIProxy($requestType, $contentId, $contextSnapshot, $nodesSnapshot, $userInput);
        
        $processingTime = round((microtime(true) - $startTime) * 1000);
        
        // 요청 레코드 업데이트
        $updateRecord = new stdClass();
        $updateRecord->id = $DB->get_field('at_quantum_ai_requests', 'id', ['request_id' => $requestId]);
        $updateRecord->status = 'completed';
        $updateRecord->openai_response = json_encode($openaiResult['rawResponse'] ?? [], JSON_UNESCAPED_UNICODE);
        $updateRecord->openai_tokens_used = $openaiResult['tokensUsed'] ?? 0;
        $updateRecord->processing_time_ms = $processingTime;
        $updateRecord->completed_at = date('Y-m-d H:i:s.u');
        
        $DB->update_record('at_quantum_ai_requests', $updateRecord);
        
        // 제안 데이터 저장
        if (!empty($openaiResult['suggestion'])) {
            $suggestionId = saveSuggestion($requestId, $contentId, $openaiResult['suggestion']);
            
            echo json_encode([
                'success' => true,
                'requestId' => $requestId,
                'suggestionId' => $suggestionId,
                'suggestion' => $openaiResult['suggestion'],
                'processingTimeMs' => $processingTime
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('No suggestion generated');
        }
        
    } catch (Exception $e) {
        // 실패 상태로 업데이트
        $DB->set_field('at_quantum_ai_requests', 'status', 'failed', ['request_id' => $requestId]);
        $DB->set_field('at_quantum_ai_requests', 'error_message', $e->getMessage(), ['request_id' => $requestId]);
        throw $e;
    }
}

/**
 * 컨텐츠 스냅샷 가져오기
 */
function getContentSnapshot($contentId) {
    global $DB;
    
    // 컨텐츠 메타데이터
    $content = $DB->get_record('at_quantum_contents', ['content_id' => $contentId]);
    
    // 개념 목록
    $concepts = $DB->get_records('at_quantum_concepts', ['content_id' => $contentId, 'is_active' => 1]);
    
    return [
        'content' => $content ? (array)$content : null,
        'concepts' => array_values(array_map(function($c) {
            return [
                'concept_id' => $c->concept_id,
                'name' => $c->name,
                'icon' => $c->icon,
                'color' => $c->color
            ];
        }, $concepts))
    ];
}

/**
 * 노드/엣지 스냅샷 가져오기
 */
function getNodesSnapshot($contentId) {
    global $DB;
    
    // 노드 목록
    $nodes = $DB->get_records('at_quantum_nodes', ['content_id' => $contentId, 'is_active' => 1]);
    
    // 엣지 목록
    $edges = $DB->get_records('at_quantum_edges', ['content_id' => $contentId, 'is_active' => 1]);
    
    // 노드-개념 연결
    $nodeConcepts = $DB->get_records('at_quantum_node_concepts', ['content_id' => $contentId]);
    
    // 노드별 개념 매핑
    $nodeConceptMap = [];
    foreach ($nodeConcepts as $nc) {
        if (!isset($nodeConceptMap[$nc->node_id])) {
            $nodeConceptMap[$nc->node_id] = [];
        }
        $nodeConceptMap[$nc->node_id][] = $nc->concept_id;
    }
    
    return [
        'nodes' => array_values(array_map(function($n) use ($nodeConceptMap) {
            return [
                'node_id' => $n->node_id,
                'label' => $n->label,
                'type' => $n->type,
                'stage' => (int)$n->stage,
                'x' => (int)$n->x,
                'y' => (int)$n->y,
                'description' => $n->description,
                'concepts' => $nodeConceptMap[$n->node_id] ?? []
            ];
        }, $nodes)),
        'edges' => array_values(array_map(function($e) {
            return [
                'source' => $e->source_node_id,
                'target' => $e->target_node_id
            ];
        }, $edges))
    ];
}

/**
 * OpenAI 프록시 호출
 */
function callOpenAIProxy($requestType, $contentId, $contextSnapshot, $nodesSnapshot, $userInput = null) {
    $proxyUrl = dirname($_SERVER['SCRIPT_FILENAME']) . '/openai_proxy.php';
    
    // 내부 호출을 위한 데이터 준비
    $_POST['action'] = 'generateSuggestion';
    $_POST['requestType'] = $requestType;
    $_POST['contentId'] = $contentId;
    $_POST['contextSnapshot'] = json_encode($contextSnapshot, JSON_UNESCAPED_UNICODE);
    $_POST['nodesSnapshot'] = json_encode($nodesSnapshot, JSON_UNESCAPED_UNICODE);
    $_POST['userInput'] = $userInput;
    
    // openai_proxy.php 직접 호출
    ob_start();
    include($proxyUrl);
    $response = ob_get_clean();
    
    $result = json_decode($response, true);
    
    if (!$result || !$result['success']) {
        throw new Exception($result['error'] ?? 'OpenAI API call failed');
    }
    
    return $result;
}

/**
 * 제안 저장
 */
function saveSuggestion($requestId, $contentId, $suggestion) {
    global $DB, $USER;
    
    // 제안 ID 생성
    $suggestionId = 'SUG_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    
    // 제안 마스터 레코드
    $record = new stdClass();
    $record->suggestion_id = $suggestionId;
    $record->request_id = $requestId;
    $record->content_id = $contentId;
    $record->title = $suggestion['title'] ?? '새로운 제안';
    $record->description = $suggestion['description'] ?? '';
    $record->suggestion_type = $suggestion['type'] ?? 'new_path';
    $record->confidence_score = $suggestion['confidence'] ?? null;
    $record->status = 'pending';
    $record->created_at = date('Y-m-d H:i:s.u');
    
    $DB->insert_record('at_quantum_ai_suggestions', $record);
    
    // 제안 노드 저장
    if (!empty($suggestion['nodes'])) {
        foreach ($suggestion['nodes'] as $node) {
            $nodeRecord = new stdClass();
            $nodeRecord->suggestion_id = $suggestionId;
            $nodeRecord->proposed_node_id = $node['node_id'];
            $nodeRecord->label = $node['label'];
            $nodeRecord->type = $node['type'];
            $nodeRecord->stage = (int)$node['stage'];
            $nodeRecord->x = (int)$node['x'];
            $nodeRecord->y = (int)$node['y'];
            $nodeRecord->description = $node['description'] ?? '';
            $nodeRecord->ai_reasoning = $node['reasoning'] ?? '';
            $nodeRecord->is_new = 1;
            $nodeRecord->status = 'pending';
            $nodeRecord->created_at = date('Y-m-d H:i:s.u');
            
            $DB->insert_record('at_quantum_ai_suggestion_nodes', $nodeRecord);
        }
    }
    
    // 제안 엣지 저장
    if (!empty($suggestion['edges'])) {
        foreach ($suggestion['edges'] as $edge) {
            $edgeRecord = new stdClass();
            $edgeRecord->suggestion_id = $suggestionId;
            $edgeRecord->source_node_id = $edge['source'];
            $edgeRecord->target_node_id = $edge['target'];
            $edgeRecord->is_new = 1;
            $edgeRecord->ai_reasoning = $edge['reasoning'] ?? '';
            $edgeRecord->status = 'pending';
            $edgeRecord->created_at = date('Y-m-d H:i:s.u');
            
            $DB->insert_record('at_quantum_ai_suggestion_edges', $edgeRecord);
        }
    }
    
    // 제안 개념 저장
    if (!empty($suggestion['concepts'])) {
        foreach ($suggestion['concepts'] as $concept) {
            $conceptRecord = new stdClass();
            $conceptRecord->suggestion_id = $suggestionId;
            $conceptRecord->proposed_node_id = $concept['node_id'];
            $conceptRecord->concept_id = $concept['concept_id'];
            $conceptRecord->concept_name = $concept['name'] ?? null;
            $conceptRecord->concept_icon = $concept['icon'] ?? null;
            $conceptRecord->concept_color = $concept['color'] ?? null;
            $conceptRecord->is_new_concept = $concept['is_new'] ? 1 : 0;
            $conceptRecord->ai_reasoning = $concept['reasoning'] ?? '';
            $conceptRecord->status = 'pending';
            $conceptRecord->created_at = date('Y-m-d H:i:s.u');
            
            $DB->insert_record('at_quantum_ai_suggestion_concepts', $conceptRecord);
        }
    }
    
    return $suggestionId;
}

/**
 * 제안 조회
 */
function getSuggestion() {
    global $DB;
    
    $suggestionId = $_GET['suggestionId'] ?? $_POST['suggestionId'] ?? null;
    
    if (!$suggestionId) {
        throw new Exception('suggestionId is required');
    }
    
    // 제안 마스터
    $suggestion = $DB->get_record('at_quantum_ai_suggestions', ['suggestion_id' => $suggestionId]);
    
    if (!$suggestion) {
        throw new Exception('Suggestion not found');
    }
    
    // 제안 노드
    $nodes = $DB->get_records('at_quantum_ai_suggestion_nodes', ['suggestion_id' => $suggestionId]);
    
    // 제안 엣지
    $edges = $DB->get_records('at_quantum_ai_suggestion_edges', ['suggestion_id' => $suggestionId]);
    
    // 제안 개념
    $concepts = $DB->get_records('at_quantum_ai_suggestion_concepts', ['suggestion_id' => $suggestionId]);
    
    echo json_encode([
        'success' => true,
        'suggestion' => [
            'suggestionId' => $suggestion->suggestion_id,
            'requestId' => $suggestion->request_id,
            'contentId' => $suggestion->content_id,
            'title' => $suggestion->title,
            'description' => $suggestion->description,
            'type' => $suggestion->suggestion_type,
            'confidence' => $suggestion->confidence_score,
            'status' => $suggestion->status,
            'nodes' => array_values(array_map(function($n) {
                return [
                    'node_id' => $n->proposed_node_id,
                    'label' => $n->label,
                    'type' => $n->type,
                    'stage' => (int)$n->stage,
                    'x' => (int)$n->x,
                    'y' => (int)$n->y,
                    'description' => $n->description,
                    'reasoning' => $n->ai_reasoning,
                    'isNew' => (bool)$n->is_new
                ];
            }, $nodes)),
            'edges' => array_values(array_map(function($e) {
                return [
                    'source' => $e->source_node_id,
                    'target' => $e->target_node_id,
                    'reasoning' => $e->ai_reasoning,
                    'isNew' => (bool)$e->is_new
                ];
            }, $edges)),
            'concepts' => array_values(array_map(function($c) {
                return [
                    'node_id' => $c->proposed_node_id,
                    'concept_id' => $c->concept_id,
                    'name' => $c->concept_name,
                    'icon' => $c->concept_icon,
                    'color' => $c->concept_color,
                    'isNewConcept' => (bool)$c->is_new_concept,
                    'reasoning' => $c->ai_reasoning
                ];
            }, $concepts))
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 대기 중인 제안 목록 조회
 */
function getPendingSuggestions() {
    global $DB;
    
    $contentId = $_GET['contentId'] ?? $_POST['contentId'] ?? null;
    
    if (!$contentId) {
        throw new Exception('contentId is required');
    }
    
    $suggestions = $DB->get_records_sql(
        "SELECT s.*, r.request_type, r.user_id 
         FROM {at_quantum_ai_suggestions} s
         JOIN {at_quantum_ai_requests} r ON s.request_id = r.request_id
         WHERE s.content_id = ? AND s.status = 'pending'
         ORDER BY s.created_at DESC",
        [$contentId]
    );
    
    echo json_encode([
        'success' => true,
        'suggestions' => array_values(array_map(function($s) {
            return [
                'suggestionId' => $s->suggestion_id,
                'requestId' => $s->request_id,
                'title' => $s->title,
                'description' => $s->description,
                'type' => $s->suggestion_type,
                'requestType' => $s->request_type,
                'userId' => $s->user_id,
                'confidence' => $s->confidence_score,
                'createdAt' => $s->created_at
            ];
        }, $suggestions))
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 제안 승인
 */
function approveSuggestion() {
    global $DB, $USER;
    
    $suggestionId = $_POST['suggestionId'] ?? null;
    
    if (!$suggestionId) {
        throw new Exception('suggestionId is required');
    }
    
    // 제안 조회
    $suggestion = $DB->get_record('at_quantum_ai_suggestions', ['suggestion_id' => $suggestionId]);
    
    if (!$suggestion) {
        throw new Exception('Suggestion not found');
    }
    
    // 요청자 정보 조회
    $request = $DB->get_record('at_quantum_ai_requests', ['request_id' => $suggestion->request_id]);
    
    // 권한 검증
    if (!canManageSuggestion($request->user_id)) {
        throw new Exception('Permission denied: 본인이 생성한 제안이거나 선생님 권한이 필요합니다.');
    }
    
    // 버전 저장 (롤백용)
    require_once(dirname(__FILE__) . '/quantum_version_api.php');
    saveVersionSnapshot($suggestion->content_id, 'ai_suggestion', $suggestionId);
    
    // 실제 데이터에 반영
    applySuggestion($suggestionId, $suggestion->content_id);
    
    // 제안 상태 업데이트
    $updateRecord = new stdClass();
    $updateRecord->id = $suggestion->id;
    $updateRecord->status = 'applied';
    $updateRecord->reviewed_by = $USER->id;
    $updateRecord->applied_at = date('Y-m-d H:i:s.u');
    $updateRecord->updated_at = date('Y-m-d H:i:s.u');
    
    $DB->update_record('at_quantum_ai_suggestions', $updateRecord);
    
    echo json_encode([
        'success' => true,
        'message' => '제안이 승인되어 인지맵에 반영되었습니다.',
        'suggestionId' => $suggestionId
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 제안을 실제 데이터에 반영
 */
function applySuggestion($suggestionId, $contentId) {
    global $DB;
    
    // 제안 노드 가져오기
    $suggestedNodes = $DB->get_records('at_quantum_ai_suggestion_nodes', ['suggestion_id' => $suggestionId, 'status' => 'pending']);
    
    foreach ($suggestedNodes as $node) {
        $nodeRecord = new stdClass();
        $nodeRecord->node_id = $node->proposed_node_id;
        $nodeRecord->content_id = $contentId;
        $nodeRecord->label = $node->label;
        $nodeRecord->type = $node->type;
        $nodeRecord->stage = $node->stage;
        $nodeRecord->x = $node->x;
        $nodeRecord->y = $node->y;
        $nodeRecord->description = $node->description;
        $nodeRecord->is_active = 1;
        $nodeRecord->created_at = date('Y-m-d H:i:s');
        
        $DB->insert_record('at_quantum_nodes', $nodeRecord);
        
        // 제안 노드 상태 업데이트
        $DB->set_field('at_quantum_ai_suggestion_nodes', 'status', 'approved', ['id' => $node->id]);
    }
    
    // 제안 엣지 가져오기
    $suggestedEdges = $DB->get_records('at_quantum_ai_suggestion_edges', ['suggestion_id' => $suggestionId, 'status' => 'pending']);
    
    foreach ($suggestedEdges as $edge) {
        $edgeRecord = new stdClass();
        $edgeRecord->source_node_id = $edge->source_node_id;
        $edgeRecord->target_node_id = $edge->target_node_id;
        $edgeRecord->content_id = $contentId;
        $edgeRecord->is_active = 1;
        $edgeRecord->created_at = date('Y-m-d H:i:s');
        
        $DB->insert_record('at_quantum_edges', $edgeRecord);
        
        // 제안 엣지 상태 업데이트
        $DB->set_field('at_quantum_ai_suggestion_edges', 'status', 'approved', ['id' => $edge->id]);
    }
    
    // 제안 개념 가져오기
    $suggestedConcepts = $DB->get_records('at_quantum_ai_suggestion_concepts', ['suggestion_id' => $suggestionId, 'status' => 'pending']);
    
    foreach ($suggestedConcepts as $concept) {
        // 새로운 개념인 경우 개념 테이블에 추가
        if ($concept->is_new_concept) {
            $conceptRecord = new stdClass();
            $conceptRecord->concept_id = $concept->concept_id;
            $conceptRecord->content_id = $contentId;
            $conceptRecord->name = $concept->concept_name;
            $conceptRecord->icon = $concept->concept_icon;
            $conceptRecord->color = $concept->concept_color;
            $conceptRecord->is_active = 1;
            $conceptRecord->created_at = date('Y-m-d H:i:s');
            
            $DB->insert_record('at_quantum_concepts', $conceptRecord);
        }
        
        // 노드-개념 연결
        $nodeConceptRecord = new stdClass();
        $nodeConceptRecord->node_id = $concept->proposed_node_id;
        $nodeConceptRecord->concept_id = $concept->concept_id;
        $nodeConceptRecord->content_id = $contentId;
        $nodeConceptRecord->created_at = date('Y-m-d H:i:s');
        
        $DB->insert_record('at_quantum_node_concepts', $nodeConceptRecord);
        
        // 제안 개념 상태 업데이트
        $DB->set_field('at_quantum_ai_suggestion_concepts', 'status', 'approved', ['id' => $concept->id]);
    }
}

/**
 * 제안 거절
 */
function rejectSuggestion() {
    global $DB, $USER;
    
    $suggestionId = $_POST['suggestionId'] ?? null;
    $reviewComment = $_POST['reviewComment'] ?? null;
    
    if (!$suggestionId) {
        throw new Exception('suggestionId is required');
    }
    
    // 제안 조회
    $suggestion = $DB->get_record('at_quantum_ai_suggestions', ['suggestion_id' => $suggestionId]);
    
    if (!$suggestion) {
        throw new Exception('Suggestion not found');
    }
    
    // 요청자 정보 조회
    $request = $DB->get_record('at_quantum_ai_requests', ['request_id' => $suggestion->request_id]);
    
    // 권한 검증
    if (!canManageSuggestion($request->user_id)) {
        throw new Exception('Permission denied: 본인이 생성한 제안이거나 선생님 권한이 필요합니다.');
    }
    
    // 제안 상태 업데이트
    $updateRecord = new stdClass();
    $updateRecord->id = $suggestion->id;
    $updateRecord->status = 'rejected';
    $updateRecord->reviewed_by = $USER->id;
    $updateRecord->review_comment = $reviewComment;
    $updateRecord->updated_at = date('Y-m-d H:i:s.u');
    
    $DB->update_record('at_quantum_ai_suggestions', $updateRecord);
    
    // 하위 항목들도 거절 처리
    $DB->set_field('at_quantum_ai_suggestion_nodes', 'status', 'rejected', ['suggestion_id' => $suggestionId]);
    $DB->set_field('at_quantum_ai_suggestion_edges', 'status', 'rejected', ['suggestion_id' => $suggestionId]);
    $DB->set_field('at_quantum_ai_suggestion_concepts', 'status', 'rejected', ['suggestion_id' => $suggestionId]);
    
    echo json_encode([
        'success' => true,
        'message' => '제안이 거절되었습니다.',
        'suggestionId' => $suggestionId
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 컨텐츠 데이터 조회 (프론트엔드용)
 */
function getContentData() {
    global $DB;
    
    $contentId = $_GET['contentId'] ?? $_POST['contentId'] ?? null;
    
    if (!$contentId) {
        throw new Exception('contentId is required');
    }
    
    $contextSnapshot = getContentSnapshot($contentId);
    $nodesSnapshot = getNodesSnapshot($contentId);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'context' => $contextSnapshot,
            'nodes' => $nodesSnapshot
        ]
    ], JSON_UNESCAPED_UNICODE);
}


