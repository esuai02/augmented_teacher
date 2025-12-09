<?php
/**
 * Project API for Holonic WXSPERTA
 * 무한 재귀 프로젝트 관리 API
 */

require_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");
require_once("approval_system.php");

require_login();

class ProjectAPI {
    private $db;
    private $approvalSystem;
    
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->approvalSystem = new ApprovalSystem();
    }
    
    /**
     * API 라우터
     */
    public function handle($action, $data) {
        switch ($action) {
            case 'get_agent_projects':
                return $this->getAgentProjects($data['agent_id']);
                
            case 'create_project':
                return $this->createProject($data);
                
            case 'update_project':
                return $this->updateProject($data);
                
            case 'get_project_tree':
                return $this->getProjectTree($data['project_id']);
                
            case 'delete_project':
                return $this->deleteProject($data['project_id']);
                
            default:
                return ['success' => false, 'error' => 'Invalid action'];
        }
    }
    
    /**
     * 에이전트의 프로젝트 목록 가져오기
     */
    private function getAgentProjects($agent_id) {
        try {
            $projects = $this->db->get_records_sql("
                SELECT p.*, a.name as agent_name, a.icon as agent_icon
                FROM {wxsperta_projects} p
                JOIN {wxsperta_agents} a ON p.agent_id = a.id
                WHERE p.agent_id = ? AND p.deleted = 0
                ORDER BY p.parent_project_id ASC, p.created_at DESC
            ", [$agent_id]);
            
            // WXSPERTA 레이어 파싱
            foreach ($projects as &$project) {
                if ($project->wxsperta_layers) {
                    $project->wxsperta_layers = json_decode($project->wxsperta_layers, true);
                }
            }
            
            return [
                'success' => true,
                'projects' => array_values($projects)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 새 프로젝트 생성 (무한 재귀 지원)
     */
    private function createProject($data) {
        global $USER;
        
        try {
            $agent_id = $data['agent_id'];
            $parent_project_id = $data['parent_project_id'] ?? null;
            $project_data = $data['project_data'];
            
            // 재귀 깊이 확인
            $depth_level = 0;
            if ($parent_project_id) {
                $parent = $this->db->get_record('wxsperta_projects', ['id' => $parent_project_id]);
                if ($parent) {
                    $depth_level = $parent->depth_level + 1;
                    
                    // 최대 깊이 체크
                    if ($depth_level > 10) {
                        return [
                            'success' => false,
                            'error' => '최대 프로젝트 깊이(10 레벨)를 초과했습니다.'
                        ];
                    }
                }
            }
            
            // 중복 확인 (Jaccard 유사도)
            $existing_projects = $this->db->get_records('wxsperta_projects', [
                'agent_id' => $agent_id,
                'deleted' => 0
            ]);
            
            foreach ($existing_projects as $existing) {
                $similarity = $this->calculateSimilarity(
                    $project_data['title'],
                    $existing->title
                );
                
                if ($similarity > 0.8) {
                    return [
                        'success' => false,
                        'error' => '유사한 프로젝트가 이미 존재합니다: ' . $existing->title
                    ];
                }
            }
            
            // 프로젝트 생성
            $project = new stdClass();
            $project->agent_id = $agent_id;
            $project->parent_project_id = $parent_project_id;
            $project->title = $project_data['title'];
            $project->description = $project_data['description'] ?? '';
            $project->wxsperta_layers = json_encode($project_data['wxsperta_layers'] ?? []);
            $project->depth_level = $depth_level;
            $project->status = 'active';
            $project->priority = $project_data['priority'] ?? 50;
            $project->created_by = $USER->id;
            $project->created_at = time();
            $project->updated_at = time();
            $project->deleted = 0;
            
            $project_id = $this->db->insert_record('wxsperta_projects', $project);
            
            // 승인 요청 생성 (깊이가 3 이상인 경우)
            if ($depth_level >= 3) {
                $this->approvalSystem->createApprovalRequest(
                    'deep_project_create',
                    'user',
                    $USER->id,
                    [
                        'project_id' => $project_id,
                        'title' => $project->title,
                        'depth' => $depth_level
                    ],
                    $agent_id,
                    $USER->id
                );
            }
            
            // 이벤트 발행
            $this->db->insert_record('wxsperta_event_bus', [
                'event_type' => 'project_created',
                'emitter_type' => 'user',
                'emitter_id' => $USER->id,
                'payload' => json_encode([
                    'project_id' => $project_id,
                    'agent_id' => $agent_id,
                    'depth' => $depth_level
                ]),
                'status' => 'pending',
                'created_at' => time()
            ]);
            
            return [
                'success' => true,
                'project_id' => $project_id,
                'message' => '프로젝트가 생성되었습니다.'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 프로젝트 업데이트
     */
    private function updateProject($data) {
        global $USER;
        
        try {
            $project_id = $data['project_id'];
            $updates = $data['updates'];
            
            $project = $this->db->get_record('wxsperta_projects', ['id' => $project_id]);
            if (!$project) {
                return ['success' => false, 'error' => '프로젝트를 찾을 수 없습니다.'];
            }
            
            // 업데이트 적용
            foreach ($updates as $key => $value) {
                if (property_exists($project, $key)) {
                    if ($key === 'wxsperta_layers') {
                        $project->$key = json_encode($value);
                    } else {
                        $project->$key = $value;
                    }
                }
            }
            
            $project->updated_at = time();
            
            $this->db->update_record('wxsperta_projects', $project);
            
            return [
                'success' => true,
                'message' => '프로젝트가 업데이트되었습니다.'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 프로젝트 트리 구조 가져오기
     */
    private function getProjectTree($project_id) {
        try {
            $tree = [];
            $this->buildProjectTree($project_id, $tree, 0);
            
            return [
                'success' => true,
                'tree' => $tree
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 재귀적으로 프로젝트 트리 구성
     */
    private function buildProjectTree($parent_id, &$tree, $depth) {
        if ($depth > 10) return; // 무한 재귀 방지
        
        $children = $this->db->get_records('wxsperta_projects', [
            'parent_project_id' => $parent_id,
            'deleted' => 0
        ]);
        
        foreach ($children as $child) {
            $node = [
                'id' => $child->id,
                'title' => $child->title,
                'description' => $child->description,
                'status' => $child->status,
                'depth_level' => $child->depth_level,
                'children' => []
            ];
            
            $this->buildProjectTree($child->id, $node['children'], $depth + 1);
            
            $tree[] = $node;
        }
    }
    
    /**
     * 프로젝트 삭제 (소프트 삭제)
     */
    private function deleteProject($project_id) {
        global $USER;
        
        try {
            $project = $this->db->get_record('wxsperta_projects', ['id' => $project_id]);
            if (!$project) {
                return ['success' => false, 'error' => '프로젝트를 찾을 수 없습니다.'];
            }
            
            // 권한 확인
            if ($project->created_by != $USER->id) {
                return ['success' => false, 'error' => '삭제 권한이 없습니다.'];
            }
            
            // 하위 프로젝트도 함께 삭제
            $this->softDeleteProjectTree($project_id);
            
            return [
                'success' => true,
                'message' => '프로젝트가 삭제되었습니다.'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 재귀적으로 프로젝트 트리 삭제
     */
    private function softDeleteProjectTree($project_id) {
        // 현재 프로젝트 삭제
        $this->db->execute("
            UPDATE {wxsperta_projects} 
            SET deleted = 1, updated_at = ?
            WHERE id = ?
        ", [time(), $project_id]);
        
        // 하위 프로젝트 찾기
        $children = $this->db->get_records('wxsperta_projects', [
            'parent_project_id' => $project_id,
            'deleted' => 0
        ]);
        
        // 재귀적으로 하위 프로젝트 삭제
        foreach ($children as $child) {
            $this->softDeleteProjectTree($child->id);
        }
    }
    
    /**
     * 문자열 유사도 계산 (Jaccard)
     */
    private function calculateSimilarity($str1, $str2) {
        $str1 = mb_strtolower($str1);
        $str2 = mb_strtolower($str2);
        
        $tokens1 = explode(' ', $str1);
        $tokens2 = explode(' ', $str2);
        
        $intersection = count(array_intersect($tokens1, $tokens2));
        $union = count(array_unique(array_merge($tokens1, $tokens2)));
        
        if ($union == 0) return 0;
        
        return $intersection / $union;
    }
}

// API 엔드포인트
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    
    $api = new ProjectAPI();
    $result = $api->handle($input['action'], $input);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// GET 요청 처리 (프로젝트 목록)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $api = new ProjectAPI();
    
    if ($_GET['action'] === 'get_agent_projects' && isset($_GET['agent_id'])) {
        $result = $api->handle('get_agent_projects', ['agent_id' => $_GET['agent_id']]);
    } else {
        $result = ['success' => false, 'error' => 'Invalid GET request'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>