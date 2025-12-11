<?php
/**
 * Quantum Modeling AI 성장 API
 * 
 * AI 요청 생성, 제안 조회, 승인/거절 기능 제공
 * 
 * @package AugmentedTeacher\TeachingSupport\AItutor\UI\API
 * @version 1.0.0
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
        echo json_encode(['success' => false, 'error' => 'Moodle config.php not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Moodle 로드 실패: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
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
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'error_location' => "$currentFile:" . $e->getLine()], JSON_UNESCAPED_UNICODE);
}

function getUserRole() {
    global $DB, $USER;
    $userrole = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = '22'", [$USER->id]);
    return $userrole ? $userrole->data : 'student';
}

function canManageSuggestion($suggestionUserId) {
    global $USER;
    $role = getUserRole();
    if ($role === 'teacher' || $role === 'admin') return true;
    return $USER->id == $suggestionUserId;
}

function createRequest() {
    global $DB, $USER;
    
    $contentId = $_POST['contentId'] ?? null;
    $requestType = $_POST['requestType'] ?? null;
    $userInput = $_POST['userInput'] ?? null;
    
    if (!$contentId || !$requestType) {
        throw new Exception('contentId and requestType are required');
    }
    
    $validTypes = ['new_solution', 'misconception', 'custom_input'];
    if (!in_array($requestType, $validTypes)) {
        throw new Exception('Invalid requestType');
    }
    
    if ($requestType === 'custom_input' && empty($userInput)) {
        throw new Exception('userInput is required for custom_input');
    }
    
    $requestId = 'REQ_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    
    $record = new stdClass();
    $record->request_id = $requestId;
    $record->user_id = $USER->id;
    $record->content_id = $contentId;
    $record->request_type = $requestType;
    $record->status = 'pending';
    $record->user_input = $userInput;
    $record->openai_model = 'gpt-4o';
    $record->created_at = date('Y-m-d H:i:s');
    
    $DB->insert_record('at_quantum_ai_requests', $record);
    
    // OpenAI 프록시 호출
    $_POST['action'] = 'generateSuggestion';
    ob_start();
    include(dirname(__FILE__) . '/openai_proxy.php');
    $response = ob_get_clean();
    
    $result = json_decode($response, true);
    
    if (!$result || !$result['success']) {
        $DB->set_field('at_quantum_ai_requests', 'status', 'failed', ['request_id' => $requestId]);
        throw new Exception($result['error'] ?? 'OpenAI API 호출 실패');
    }
    
    $DB->set_field('at_quantum_ai_requests', 'status', 'completed', ['request_id' => $requestId]);
    
    // 제안 저장
    $suggestionId = saveSuggestion($requestId, $contentId, $result['suggestion']);
    
    echo json_encode([
        'success' => true,
        'requestId' => $requestId,
        'suggestionId' => $suggestionId,
        'suggestion' => $result['suggestion']
    ], JSON_UNESCAPED_UNICODE);
}

function saveSuggestion($requestId, $contentId, $suggestion) {
    global $DB, $USER;
    
    $suggestionId = 'SUG_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    
    $record = new stdClass();
    $record->suggestion_id = $suggestionId;
    $record->request_id = $requestId;
    $record->content_id = $contentId;
    $record->title = $suggestion['title'] ?? '새로운 제안';
    $record->description = $suggestion['description'] ?? '';
    $record->suggestion_type = $suggestion['type'] ?? 'new_path';
    $record->confidence_score = $suggestion['confidence'] ?? null;
    $record->status = 'pending';
    $record->created_at = date('Y-m-d H:i:s');
    
    $DB->insert_record('at_quantum_ai_suggestions', $record);
    
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
            $nodeRecord->created_at = date('Y-m-d H:i:s');
            $DB->insert_record('at_quantum_ai_suggestion_nodes', $nodeRecord);
        }
    }
    
    if (!empty($suggestion['edges'])) {
        foreach ($suggestion['edges'] as $edge) {
            $edgeRecord = new stdClass();
            $edgeRecord->suggestion_id = $suggestionId;
            $edgeRecord->source_node_id = $edge['source'];
            $edgeRecord->target_node_id = $edge['target'];
            $edgeRecord->is_new = 1;
            $edgeRecord->ai_reasoning = $edge['reasoning'] ?? '';
            $edgeRecord->status = 'pending';
            $edgeRecord->created_at = date('Y-m-d H:i:s');
            $DB->insert_record('at_quantum_ai_suggestion_edges', $edgeRecord);
        }
    }
    
    return $suggestionId;
}

function getSuggestion() {
    global $DB;
    
    $suggestionId = $_GET['suggestionId'] ?? $_POST['suggestionId'] ?? null;
    if (!$suggestionId) throw new Exception('suggestionId is required');
    
    $suggestion = $DB->get_record('at_quantum_ai_suggestions', ['suggestion_id' => $suggestionId]);
    if (!$suggestion) throw new Exception('Suggestion not found');
    
    $nodes = $DB->get_records('at_quantum_ai_suggestion_nodes', ['suggestion_id' => $suggestionId]);
    $edges = $DB->get_records('at_quantum_ai_suggestion_edges', ['suggestion_id' => $suggestionId]);
    
    echo json_encode([
        'success' => true,
        'suggestion' => [
            'suggestionId' => $suggestion->suggestion_id,
            'title' => $suggestion->title,
            'description' => $suggestion->description,
            'type' => $suggestion->suggestion_type,
            'confidence' => $suggestion->confidence_score,
            'status' => $suggestion->status,
            'nodes' => array_values(array_map(function($n) {
                return ['node_id' => $n->proposed_node_id, 'label' => $n->label, 'type' => $n->type, 'stage' => (int)$n->stage, 'x' => (int)$n->x, 'y' => (int)$n->y];
            }, $nodes)),
            'edges' => array_values(array_map(function($e) {
                return ['source' => $e->source_node_id, 'target' => $e->target_node_id];
            }, $edges))
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function getPendingSuggestions() {
    global $DB;
    
    $contentId = $_GET['contentId'] ?? $_POST['contentId'] ?? null;
    if (!$contentId) throw new Exception('contentId is required');
    
    $suggestions = $DB->get_records('at_quantum_ai_suggestions', ['content_id' => $contentId, 'status' => 'pending']);
    
    echo json_encode([
        'success' => true,
        'suggestions' => array_values(array_map(function($s) {
            return ['suggestionId' => $s->suggestion_id, 'title' => $s->title, 'type' => $s->suggestion_type];
        }, $suggestions))
    ], JSON_UNESCAPED_UNICODE);
}

function approveSuggestion() {
    global $DB, $USER;
    
    $suggestionId = $_POST['suggestionId'] ?? null;
    if (!$suggestionId) throw new Exception('suggestionId is required');
    
    $suggestion = $DB->get_record('at_quantum_ai_suggestions', ['suggestion_id' => $suggestionId]);
    if (!$suggestion) throw new Exception('Suggestion not found');
    
    $request = $DB->get_record('at_quantum_ai_requests', ['request_id' => $suggestion->request_id]);
    if ($request && !canManageSuggestion($request->user_id)) {
        throw new Exception('Permission denied');
    }
    
    // 버전 저장 (선택적 - 실패해도 계속 진행)
    try {
        require_once(dirname(__FILE__) . '/quantum_version_api.php');
        if (function_exists('saveVersionSnapshot')) {
            saveVersionSnapshot($suggestion->content_id, 'ai_suggestion', $suggestionId);
        }
    } catch (Exception $e) {
        // 버전 저장 실패해도 계속 진행
        error_log("[quantum_growth_api] 버전 저장 실패: " . $e->getMessage());
    }
    
    // 실제 데이터 반영
    try {
        applySuggestion($suggestionId, $suggestion->content_id);
    } catch (Exception $e) {
        throw new Exception('노드/엣지 반영 실패: ' . $e->getMessage());
    }
    
    // 제안 상태 업데이트
    try {
        $updateRecord = new stdClass();
        $updateRecord->id = $suggestion->id;
        $updateRecord->status = 'applied';
        $updateRecord->reviewed_by = $USER->id;
        $updateRecord->applied_at = date('Y-m-d H:i:s');
        $DB->update_record('at_quantum_ai_suggestions', $updateRecord);
    } catch (Exception $e) {
        throw new Exception('제안 상태 업데이트 실패: ' . $e->getMessage());
    }
    
    echo json_encode(['success' => true, 'message' => '제안이 승인되었습니다.'], JSON_UNESCAPED_UNICODE);
}

function applySuggestion($suggestionId, $contentId) {
    global $DB;
    
    // 테이블 존재 여부 확인 (선택적)
    $tables = ['at_quantum_nodes', 'at_quantum_edges'];
    
    $nodes = $DB->get_records('at_quantum_ai_suggestion_nodes', ['suggestion_id' => $suggestionId]);
    foreach ($nodes as $node) {
        try {
            // 기존 노드 확인 (중복 방지)
            $existing = $DB->get_record('at_quantum_nodes', [
                'node_id' => $node->proposed_node_id, 
                'content_id' => $contentId
            ]);
            
            if ($existing) {
                // 기존 노드 업데이트
                $existing->label = $node->label;
                $existing->type = $node->type;
                $existing->stage = $node->stage;
                $existing->x = $node->x;
                $existing->y = $node->y;
                $existing->description = $node->description;
                $existing->is_active = 1;
                $existing->updated_at = date('Y-m-d H:i:s');
                $DB->update_record('at_quantum_nodes', $existing);
            } else {
                // 새 노드 삽입
                $record = new stdClass();
                $record->node_id = $node->proposed_node_id;
                $record->content_id = $contentId;
                $record->label = $node->label;
                $record->type = $node->type;
                $record->stage = $node->stage;
                $record->x = $node->x;
                $record->y = $node->y;
                $record->description = $node->description;
                $record->is_active = 1;
                $record->created_at = date('Y-m-d H:i:s');
                $DB->insert_record('at_quantum_nodes', $record);
            }
            
            $DB->set_field('at_quantum_ai_suggestion_nodes', 'status', 'approved', ['id' => $node->id]);
        } catch (Exception $e) {
            throw new Exception("노드 '{$node->label}' 저장 실패: " . $e->getMessage());
        }
    }
    
    $edges = $DB->get_records('at_quantum_ai_suggestion_edges', ['suggestion_id' => $suggestionId]);
    foreach ($edges as $edge) {
        try {
            // 기존 엣지 확인 (중복 방지)
            $existing = $DB->get_record('at_quantum_edges', [
                'source_node_id' => $edge->source_node_id,
                'target_node_id' => $edge->target_node_id,
                'content_id' => $contentId
            ]);
            
            if (!$existing) {
                $record = new stdClass();
                $record->source_node_id = $edge->source_node_id;
                $record->target_node_id = $edge->target_node_id;
                $record->content_id = $contentId;
                $record->is_active = 1;
                $record->created_at = date('Y-m-d H:i:s');
                $DB->insert_record('at_quantum_edges', $record);
            } else {
                // 기존 엣지 활성화
                $DB->set_field('at_quantum_edges', 'is_active', 1, ['id' => $existing->id]);
            }
            
            $DB->set_field('at_quantum_ai_suggestion_edges', 'status', 'approved', ['id' => $edge->id]);
        } catch (Exception $e) {
            throw new Exception("엣지 '{$edge->source_node_id}->{$edge->target_node_id}' 저장 실패: " . $e->getMessage());
        }
    }
}

function rejectSuggestion() {
    global $DB, $USER;
    
    $suggestionId = $_POST['suggestionId'] ?? null;
    if (!$suggestionId) throw new Exception('suggestionId is required');
    
    $suggestion = $DB->get_record('at_quantum_ai_suggestions', ['suggestion_id' => $suggestionId]);
    if (!$suggestion) throw new Exception('Suggestion not found');
    
    $request = $DB->get_record('at_quantum_ai_requests', ['request_id' => $suggestion->request_id]);
    if (!canManageSuggestion($request->user_id)) {
        throw new Exception('Permission denied');
    }
    
    $updateRecord = new stdClass();
    $updateRecord->id = $suggestion->id;
    $updateRecord->status = 'rejected';
    $updateRecord->reviewed_by = $USER->id;
    $DB->update_record('at_quantum_ai_suggestions', $updateRecord);
    
    $DB->set_field('at_quantum_ai_suggestion_nodes', 'status', 'rejected', ['suggestion_id' => $suggestionId]);
    $DB->set_field('at_quantum_ai_suggestion_edges', 'status', 'rejected', ['suggestion_id' => $suggestionId]);
    
    echo json_encode(['success' => true, 'message' => '제안이 거절되었습니다.'], JSON_UNESCAPED_UNICODE);
}

