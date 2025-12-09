<?php
/**
 * Student Approval System for Holonic WXSPERTA
 * 학생 승인 기반 자동 업데이트 시스템
 */

require_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");
require_once("event_bus.php");

class ApprovalSystem {
    private $db;
    private $eventBus;
    
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->eventBus = new EventBus();
    }
    
    /**
     * 승인 요청 생성
     */
    public function createApprovalRequest($type, $entity_type, $entity_id, $changes, $requested_by, $user_id) {
        $request = new stdClass();
        $request->user_id = $user_id;
        $request->request_type = $type;
        $request->entity_type = $entity_type;
        $request->entity_id = $entity_id;
        $request->change_description = $this->generateDescription($type, $changes);
        $request->old_value = json_encode($this->getCurrentValue($entity_type, $entity_id, $type));
        $request->new_value = json_encode($changes);
        $request->requested_by_agent_id = $requested_by;
        $request->status = 'pending';
        $request->requested_at = time();
        $request->expires_at = time() + (24 * 3600); // 24시간 후 만료
        
        $request_id = $this->db->insert_record('wxsperta_approval_requests', $request);
        
        // 사용자에게 알림 발송
        $this->notifyUser($user_id, $request_id, $request);
        
        wxsperta_log("Approval request created: ID=$request_id, Type=$type", 'INFO');
        
        return $request_id;
    }
    
    /**
     * 승인 요청 목록 가져오기
     */
    public function getPendingRequests($user_id) {
        return $this->db->get_records_sql("
            SELECT ar.*, a.name as agent_name, a.icon as agent_icon
            FROM {wxsperta_approval_requests} ar
            LEFT JOIN {wxsperta_agents} a ON ar.requested_by_agent_id = a.id
            WHERE ar.user_id = ? 
            AND ar.status = 'pending'
            AND ar.expires_at > ?
            ORDER BY ar.requested_at DESC
        ", [$user_id, time()]);
    }
    
    /**
     * 승인 처리
     */
    public function approveRequest($request_id, $user_id) {
        $request = $this->db->get_record('wxsperta_approval_requests', [
            'id' => $request_id,
            'user_id' => $user_id,
            'status' => 'pending'
        ]);
        
        if (!$request) {
            throw new Exception('Invalid or expired approval request');
        }
        
        // 상태 업데이트
        $request->status = 'approved';
        $request->responded_at = time();
        $this->db->update_record('wxsperta_approval_requests', $request);
        
        // 실제 변경 적용
        $this->applyChanges($request);
        
        // 이벤트 발행
        $this->eventBus->emit('approval_granted', 'user', $user_id, [
            'request_id' => $request_id,
            'request_type' => $request->request_type,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id
        ]);
        
        wxsperta_log("Approval granted: Request ID=$request_id", 'INFO');
        
        return true;
    }
    
    /**
     * 거부 처리
     */
    public function rejectRequest($request_id, $user_id, $reason = '') {
        $request = $this->db->get_record('wxsperta_approval_requests', [
            'id' => $request_id,
            'user_id' => $user_id,
            'status' => 'pending'
        ]);
        
        if (!$request) {
            throw new Exception('Invalid or expired approval request');
        }
        
        // 상태 업데이트
        $request->status = 'rejected';
        $request->responded_at = time();
        $this->db->update_record('wxsperta_approval_requests', $request);
        
        // 이벤트 발행
        $this->eventBus->emit('approval_rejected', 'user', $user_id, [
            'request_id' => $request_id,
            'request_type' => $request->request_type,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'reason' => $reason
        ]);
        
        // 에이전트에게 피드백
        if ($request->requested_by_agent_id) {
            $this->eventBus->emit('request_rejected_feedback', 'system', 0, [
                'reason' => $reason,
                'original_request' => json_decode($request->new_value, true)
            ], ['type' => 'agent', 'id' => $request->requested_by_agent_id]);
        }
        
        wxsperta_log("Approval rejected: Request ID=$request_id", 'INFO');
        
        return true;
    }
    
    /**
     * 변경사항 적용
     */
    private function applyChanges($request) {
        $changes = json_decode($request->new_value, true);
        
        switch ($request->request_type) {
            case 'agent_update':
                $this->applyAgentUpdate($request->entity_id, $changes);
                break;
                
            case 'project_create':
                $this->createApprovedProject($request->entity_id, $changes);
                break;
                
            case 'prop_change':
                $this->applyPropertyChange($request->entity_type, $request->entity_id, $changes);
                break;
                
            case 'system_action':
                $this->executeSystemAction($changes);
                break;
        }
    }
    
    /**
     * 에이전트 업데이트 적용
     */
    private function applyAgentUpdate($agent_id, $changes) {
        $agent = $this->db->get_record('wxsperta_agents', ['id' => $agent_id]);
        if (!$agent) return;
        
        foreach ($changes as $field => $value) {
            if (property_exists($agent, $field)) {
                $agent->$field = $value;
            }
        }
        
        $agent->updated_at = time();
        $this->db->update_record('wxsperta_agents', $agent);
        
        // 변경 이력 기록
        $this->logChange('agent', $agent_id, $changes);
    }
    
    /**
     * 승인된 프로젝트 생성
     */
    private function createApprovedProject($agent_id, $project_data) {
        $project = new stdClass();
        $project->title = $project_data['title'];
        $project->description = $project_data['description'];
        $project->agent_owner_id = $agent_id;
        $project->priority = $project_data['priority'] ?? 50;
        $project->status = 'active'; // 승인된 프로젝트는 바로 활성화
        $project->created_at = time();
        
        $project_id = $this->db->insert_record('wxsperta_projects', $project);
        
        // 프로젝트 속성 설정
        if (isset($project_data['wxsperta_layers'])) {
            foreach ($project_data['wxsperta_layers'] as $layer => $content) {
                $prop = new stdClass();
                $prop->project_id = $project_id;
                $prop->layer = $layer;
                $prop->content = $content;
                $this->db->insert_record('wxsperta_project_props', $prop);
            }
        }
        
        // 프로젝트 생성 이벤트
        $this->eventBus->emit('approved_project_created', 'system', 0, [
            'project_id' => $project_id,
            'agent_id' => $agent_id
        ]);
    }
    
    /**
     * 속성 변경 적용
     */
    private function applyPropertyChange($entity_type, $entity_id, $changes) {
        foreach ($changes as $layer => $content) {
            if ($entity_type === 'agent') {
                $field = $this->camelToSnake($layer);
                $this->db->set_field('wxsperta_agents', $field, $content, ['id' => $entity_id]);
            } else if ($entity_type === 'project') {
                $existing = $this->db->get_record('wxsperta_project_props', [
                    'project_id' => $entity_id,
                    'layer' => $layer
                ]);
                
                if ($existing) {
                    $existing->content = $content;
                    $existing->updated_at = time();
                    $this->db->update_record('wxsperta_project_props', $existing);
                } else {
                    $prop = new stdClass();
                    $prop->project_id = $entity_id;
                    $prop->layer = $layer;
                    $prop->content = $content;
                    $this->db->insert_record('wxsperta_project_props', $prop);
                }
            }
        }
    }
    
    /**
     * 시스템 액션 실행
     */
    private function executeSystemAction($action_data) {
        $action_type = $action_data['type'] ?? '';
        
        switch ($action_type) {
            case 'reset_metrics':
                $this->resetMetrics($action_data['entity_type'], $action_data['entity_id']);
                break;
                
            case 'activate_agent':
                $this->db->set_field('wxsperta_agents', 'is_active', 1, 
                    ['id' => $action_data['agent_id']]);
                break;
                
            case 'suspend_project':
                $this->db->set_field('wxsperta_projects', 'status', 'suspended', 
                    ['id' => $action_data['project_id']]);
                break;
        }
    }
    
    /**
     * 사용자 알림
     */
    private function notifyUser($user_id, $request_id, $request) {
        // 알림 메시지 구성
        $agent_name = $this->getAgentName($request->requested_by_agent_id);
        $message = $this->generateNotificationMessage($request->request_type, $agent_name, 
            json_decode($request->new_value, true));
        
        // 이벤트 발행 (UI에서 처리)
        $this->eventBus->emit('approval_notification', 'system', 0, [
            'user_id' => $user_id,
            'request_id' => $request_id,
            'message' => $message,
            'type' => $request->request_type,
            'urgency' => $this->calculateUrgency($request)
        ], ['type' => 'user', 'id' => $user_id]);
        
        // 이메일 알림 (설정된 경우)
        if (ENABLE_EMAIL_NOTIFICATIONS) {
            $this->sendEmailNotification($user_id, $message);
        }
    }
    
    /**
     * 설명 생성
     */
    private function generateDescription($type, $changes) {
        $descriptions = [
            'agent_update' => '에이전트 속성 업데이트',
            'project_create' => '새 프로젝트 생성',
            'prop_change' => 'WXSPERTA 레이어 수정',
            'system_action' => '시스템 작업 실행'
        ];
        
        $base = $descriptions[$type] ?? '변경 요청';
        
        // 상세 설명 추가
        if ($type === 'agent_update' && is_array($changes)) {
            $fields = array_keys($changes);
            $base .= ': ' . implode(', ', $fields);
        } else if ($type === 'project_create' && isset($changes['title'])) {
            $base .= ': ' . $changes['title'];
        }
        
        return $base;
    }
    
    /**
     * 알림 메시지 생성
     */
    private function generateNotificationMessage($type, $agent_name, $changes) {
        switch ($type) {
            case 'agent_update':
                return "{$agent_name} 에이전트가 속성 업데이트를 요청했습니다.";
                
            case 'project_create':
                $title = $changes['title'] ?? '새 프로젝트';
                return "{$agent_name} 에이전트가 '{$title}' 프로젝트 생성을 제안했습니다.";
                
            case 'prop_change':
                $layers = array_keys($changes);
                return "{$agent_name} 에이전트가 " . implode(', ', $layers) . " 레이어 수정을 요청했습니다.";
                
            case 'system_action':
                return "시스템 작업 승인이 필요합니다: " . ($changes['description'] ?? '');
                
            default:
                return "승인이 필요한 요청이 있습니다.";
        }
    }
    
    /**
     * 현재 값 가져오기
     */
    private function getCurrentValue($entity_type, $entity_id, $request_type) {
        if ($entity_type === 'agent' && $request_type === 'agent_update') {
            $agent = $this->db->get_record('wxsperta_agents', ['id' => $entity_id]);
            if ($agent) {
                return [
                    'worldView' => $agent->world_view,
                    'context' => $agent->context,
                    'structure' => $agent->structure,
                    'process' => $agent->process,
                    'execution' => $agent->execution,
                    'reflection' => $agent->reflection,
                    'transfer' => $agent->transfer,
                    'abstraction' => $agent->abstraction
                ];
            }
        }
        
        return [];
    }
    
    /**
     * 변경 이력 기록
     */
    private function logChange($entity_type, $entity_id, $changes) {
        // 변경 이력을 이벤트로 기록
        $this->eventBus->emit('entity_changed', 'system', 0, [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'changes' => $changes,
            'timestamp' => time()
        ]);
    }
    
    /**
     * 긴급도 계산
     */
    private function calculateUrgency($request) {
        // 요청 타입별 기본 긴급도
        $urgency_map = [
            'system_action' => 'high',
            'agent_update' => 'medium',
            'project_create' => 'medium',
            'prop_change' => 'low'
        ];
        
        $base_urgency = $urgency_map[$request->request_type] ?? 'low';
        
        // 만료 시간이 가까우면 긴급도 상승
        $time_remaining = $request->expires_at - time();
        if ($time_remaining < 3600) { // 1시간 이내
            return 'high';
        } else if ($time_remaining < 21600) { // 6시간 이내
            return 'medium';
        }
        
        return $base_urgency;
    }
    
    /**
     * 만료된 요청 정리
     */
    public function cleanupExpiredRequests() {
        $expired = $this->db->get_records_sql("
            SELECT * FROM {wxsperta_approval_requests}
            WHERE status = 'pending' AND expires_at < ?
        ", [time()]);
        
        foreach ($expired as $request) {
            $request->status = 'expired';
            $this->db->update_record('wxsperta_approval_requests', $request);
            
            // 에이전트에게 만료 알림
            if ($request->requested_by_agent_id) {
                $this->eventBus->emit('request_expired', 'system', 0, [
                    'request_id' => $request->id,
                    'request_type' => $request->request_type
                ], ['type' => 'agent', 'id' => $request->requested_by_agent_id]);
            }
        }
        
        return count($expired);
    }
    
    // 헬퍼 메서드
    private function camelToSnake($camel) {
        return strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($camel)));
    }
    
    private function getAgentName($agent_id) {
        if (!$agent_id) return '시스템';
        
        $agent = $this->db->get_record('wxsperta_agents', ['id' => $agent_id]);
        return $agent ? $agent->name : "에이전트 #{$agent_id}";
    }
    
    private function resetMetrics($entity_type, $entity_id) {
        $this->db->delete_records('wxsperta_metrics', [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id
        ]);
    }
    
    private function sendEmailNotification($user_id, $message) {
        // TODO: 이메일 발송 구현
        wxsperta_log("Email notification would be sent to user $user_id: $message", 'INFO');
    }
}

// API 엔드포인트
if (isset($_GET['action']) || isset($_POST['action'])) {
    require_login();
    
    $approvalSystem = new ApprovalSystem();
    $action = $_REQUEST['action'] ?? '';
    
    header('Content-Type: application/json');
    
    try {
        switch ($action) {
            case 'get_pending':
                $requests = $approvalSystem->getPendingRequests($USER->id);
                echo json_encode(['success' => true, 'requests' => array_values($requests)]);
                break;
                
            case 'approve':
                $request_id = $_POST['request_id'] ?? 0;
                $result = $approvalSystem->approveRequest($request_id, $USER->id);
                echo json_encode(['success' => $result]);
                break;
                
            case 'reject':
                $request_id = $_POST['request_id'] ?? 0;
                $reason = $_POST['reason'] ?? '';
                $result = $approvalSystem->rejectRequest($request_id, $USER->id, $reason);
                echo json_encode(['success' => $result]);
                break;
                
            case 'cleanup':
                if (is_siteadmin()) {
                    $cleaned = $approvalSystem->cleanupExpiredRequests();
                    echo json_encode(['success' => true, 'cleaned' => $cleaned]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Admin only']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>