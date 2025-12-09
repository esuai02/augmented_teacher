<?php
/**
 * LLM Orchestrator for Holonic WXSPERTA
 * Holon-Loop: Perceive → Plan → Act → Learn
 */

require_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");
require_once("event_bus.php");

class LLMOrchestrator {
    private $db;
    private $eventBus;
    private $recursionGuard;
    
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->eventBus = new EventBus();
        $this->recursionGuard = new RecursionGuard($DB);
    }
    
    /**
     * Holon Loop 실행
     */
    public function executeHolonLoop($agent_id, $trigger_event = null) {
        // 재귀 방지 체크
        if (!$this->recursionGuard->canProceed($agent_id, 'agent')) {
            wxsperta_log("Recursion limit reached for agent $agent_id", 'WARNING');
            return false;
        }
        
        try {
            // 1. Perceive - 환경 인지
            $perception = $this->perceive($agent_id, $trigger_event);
            
            // 2. Plan - 계획 수립
            $plan = $this->plan($agent_id, $perception);
            
            // 3. Act - 실행
            $actions = $this->act($agent_id, $plan);
            
            // 4. Learn - 학습 및 업데이트
            $this->learn($agent_id, $perception, $plan, $actions);
            
            return true;
            
        } finally {
            $this->recursionGuard->release($agent_id, 'agent');
        }
    }
    
    /**
     * 1. Perceive - 환경 인지
     */
    private function perceive($agent_id, $trigger_event) {
        $agent = $this->db->get_record('wxsperta_agents', ['id' => $agent_id]);
        if (!$agent) return null;
        
        $perception = [
            'agent' => $agent,
            'trigger' => $trigger_event,
            'context' => [],
            'metrics' => [],
            'events' => []
        ];
        
        // 8층 WXSPERTA 속성 가져오기
        $props = $this->getAgentProperties($agent_id);
        $perception['wxsperta'] = $props;
        
        // 최근 이벤트 수집
        $recent_events = $this->db->get_records_sql("
            SELECT * FROM {wxsperta_event_bus}
            WHERE (target_type = 'agent' AND target_id = ?)
               OR (emitter_type = 'agent' AND emitter_id = ?)
            ORDER BY created_at DESC
            LIMIT 20
        ", [$agent_id, $agent_id]);
        $perception['events'] = $recent_events;
        
        // 현재 프로젝트 상태
        $projects = $this->db->get_records_sql("
            SELECT p.*, 
                   COUNT(sub.id) as sub_project_count,
                   AVG(COALESCE(m.kpi_value, 0)) as avg_progress
            FROM {wxsperta_projects} p
            LEFT JOIN {wxsperta_projects} sub ON sub.parent_project_id = p.id
            LEFT JOIN {wxsperta_metrics} m ON m.entity_type = 'project' 
                AND m.entity_id = p.id AND m.kpi_name = 'progress'
            WHERE p.agent_owner_id = ?
            AND p.status IN ('pending', 'active')
            GROUP BY p.id
        ", [$agent_id]);
        $perception['projects'] = $projects;
        
        // 관련 메트릭
        $metrics = $this->db->get_records_sql("
            SELECT * FROM {wxsperta_metrics}
            WHERE entity_type = 'agent' AND entity_id = ?
            ORDER BY measured_at DESC
            LIMIT 10
        ", [$agent_id]);
        $perception['metrics'] = $metrics;
        
        // 하위 에이전트 상태 (있는 경우)
        $sub_agents = $this->db->get_records('wxsperta_agents', ['parent_agent_id' => $agent_id]);
        $perception['sub_agents'] = $sub_agents;
        
        return $perception;
    }
    
    /**
     * 2. Plan - 계획 수립
     */
    private function plan($agent_id, $perception) {
        if (!$perception) return null;
        
        $agent = $perception['agent'];
        $wxsperta = $perception['wxsperta'];
        
        // LLM 프롬프트 구성
        $system_prompt = $this->buildSystemPrompt($agent, $wxsperta);
        $user_prompt = $this->buildPlanningPrompt($perception);
        
        // OpenAI Function Calling
        $functions = [
            [
                'name' => 'create_project_plan',
                'description' => 'Create a plan with sub-projects',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'analysis' => [
                            'type' => 'string',
                            'description' => 'Gap analysis between current state and goals'
                        ],
                        'projects' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'description' => ['type' => 'string'],
                                    'priority' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                                    'estimated_hours' => ['type' => 'number'],
                                    'wxsperta_layers' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'worldView' => ['type' => 'string'],
                                            'context' => ['type' => 'string'],
                                            'structure' => ['type' => 'string'],
                                            'process' => ['type' => 'string']
                                        ]
                                    ],
                                    'dependencies' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string']
                                    ]
                                ],
                                'required' => ['title', 'description', 'priority']
                            ]
                        ],
                        'updates' => [
                            'type' => 'object',
                            'description' => 'Suggested updates to agent properties'
                        ]
                    ],
                    'required' => ['analysis', 'projects']
                ]
            ]
        ];
        
        // LLM 호출
        $response = $this->callLLMWithFunctions($system_prompt, $user_prompt, $functions);
        
        if ($response && isset($response['function_call'])) {
            $plan = json_decode($response['function_call']['arguments'], true);
            $plan['agent_id'] = $agent_id;
            $plan['created_at'] = time();
            return $plan;
        }
        
        return null;
    }
    
    /**
     * 3. Act - 실행
     */
    private function act($agent_id, $plan) {
        if (!$plan || empty($plan['projects'])) return [];
        
        $actions = [];
        $created_projects = [];
        
        // 중복 체크를 위한 기존 프로젝트 로드
        $existing_projects = $this->db->get_records_sql("
            SELECT title, description FROM {wxsperta_projects}
            WHERE agent_owner_id = ? AND status != 'completed'
        ", [$agent_id]);
        
        foreach ($plan['projects'] as $project_data) {
            // 유사도 체크 (Jaccard 유사도)
            $is_duplicate = false;
            foreach ($existing_projects as $existing) {
                $similarity = $this->calculateSimilarity(
                    $project_data['title'] . ' ' . $project_data['description'],
                    $existing->title . ' ' . $existing->description
                );
                if ($similarity > 0.8) {
                    $is_duplicate = true;
                    break;
                }
            }
            
            if ($is_duplicate) {
                wxsperta_log("Skipping duplicate project: " . $project_data['title'], 'INFO');
                continue;
            }
            
            // 프로젝트 생성
            $project = new stdClass();
            $project->title = $project_data['title'];
            $project->description = $project_data['description'];
            $project->agent_owner_id = $agent_id;
            $project->priority = $project_data['priority'] ?? 50;
            $project->status = 'pending';
            $project->created_at = time();
            
            $project_id = $this->db->insert_record('wxsperta_projects', $project);
            $created_projects[] = $project_id;
            
            // 프로젝트 WXSPERTA 속성 저장
            if (isset($project_data['wxsperta_layers'])) {
                foreach ($project_data['wxsperta_layers'] as $layer => $content) {
                    $prop = new stdClass();
                    $prop->project_id = $project_id;
                    $prop->layer = $layer;
                    $prop->content = $content;
                    $this->db->insert_record('wxsperta_project_props', $prop);
                }
            }
            
            // 이벤트 발행
            $this->eventBus->emit('project_created', 'agent', $agent_id, [
                'project_id' => $project_id,
                'agent_id' => $agent_id,
                'title' => $project->title
            ]);
            
            $actions[] = [
                'type' => 'project_created',
                'project_id' => $project_id,
                'title' => $project->title
            ];
        }
        
        // 에이전트 속성 업데이트 제안이 있는 경우
        if (isset($plan['updates']) && !empty($plan['updates'])) {
            // 학생 승인 요청
            $user = $this->getAgentUser($agent_id);
            if ($user) {
                $this->eventBus->emit('agent_update_request', 'agent', $agent_id, [
                    'agent_id' => $agent_id,
                    'updates' => $plan['updates'],
                    'user_id' => $user->id,
                    'description' => 'AI suggested property updates based on recent analysis',
                    'old_values' => $this->getAgentProperties($agent_id)
                ]);
                
                $actions[] = [
                    'type' => 'update_requested',
                    'updates' => $plan['updates']
                ];
            }
        }
        
        return $actions;
    }
    
    /**
     * 4. Learn - 학습 및 업데이트
     */
    private function learn($agent_id, $perception, $plan, $actions) {
        // 실행 결과를 reflection 레이어에 기록
        $reflection_content = $this->generateReflection($perception, $plan, $actions);
        
        $this->updateAgentProperty($agent_id, 'reflection', $reflection_content);
        
        // 메트릭 업데이트
        $this->eventBus->updateMetric('agent', $agent_id, 'holon_loops_completed', 
            $this->getMetricValue($agent_id, 'holon_loops_completed') + 1);
        
        $this->eventBus->updateMetric('agent', $agent_id, 'projects_created', 
            count($actions));
        
        // 벡터 DB에 저장 (임베딩)
        $this->saveToVectorDB($agent_id, [
            'perception' => $perception,
            'plan' => $plan,
            'actions' => $actions,
            'reflection' => $reflection_content
        ]);
    }
    
    /**
     * 시스템 프롬프트 구성
     */
    private function buildSystemPrompt($agent, $wxsperta) {
        $layers_json = json_encode($wxsperta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return "You are {$agent->name}, an AI agent in the Holonic WXSPERTA system.
Your role: {$agent->description}

Your current WXSPERTA layers:
$layers_json

You operate in a Holon-Loop: Perceive → Plan → Act → Learn.
You can create sub-projects and delegate to other agents.
Always consider the hierarchical structure and avoid infinite recursion.
Respond in Korean when dealing with student-related content.";
    }
    
    /**
     * 계획 프롬프트 구성
     */
    private function buildPlanningPrompt($perception) {
        $events_summary = $this->summarizeEvents($perception['events']);
        $projects_summary = $this->summarizeProjects($perception['projects']);
        $metrics_summary = $this->summarizeMetrics($perception['metrics']);
        
        return "Current situation analysis:

Recent Events:
$events_summary

Active Projects:
$projects_summary

Performance Metrics:
$metrics_summary

Based on this perception, analyze the gap between current state and your goals.
Suggest concrete sub-projects to bridge this gap.
Consider your worldView and process layers when planning.
Ensure no duplicate projects are created.";
    }
    
    /**
     * LLM Function Calling
     */
    private function callLLMWithFunctions($system_prompt, $user_prompt, $functions) {
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_prompt]
        ];
        
        $data = [
            'model' => OPENAI_MODEL,
            'messages' => $messages,
            'functions' => $functions,
            'function_call' => 'auto',
            'temperature' => 0.7,
            'max_tokens' => 2000
        ];
        
        $ch = curl_init(OPENAI_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message'])) {
                return $result['choices'][0]['message'];
            }
        }
        
        wxsperta_log("LLM call failed: HTTP $http_code", 'ERROR');
        return null;
    }
    
    /**
     * 에이전트 속성 가져오기
     */
    private function getAgentProperties($agent_id) {
        $props = [];
        $layers = ['worldView', 'context', 'structure', 'process', 
                   'execution', 'reflection', 'transfer', 'abstraction'];
        
        $agent = $this->db->get_record('wxsperta_agents', ['id' => $agent_id]);
        
        foreach ($layers as $layer) {
            $field = $this->camelToSnake($layer);
            $props[$layer] = $agent->$field ?? '';
        }
        
        return $props;
    }
    
    /**
     * 에이전트 속성 업데이트
     */
    private function updateAgentProperty($agent_id, $layer, $content) {
        $field = $this->camelToSnake($layer);
        $this->db->set_field('wxsperta_agents', $field, $content, ['id' => $agent_id]);
    }
    
    /**
     * 유사도 계산 (Jaccard)
     */
    private function calculateSimilarity($text1, $text2) {
        $words1 = array_unique(explode(' ', mb_strtolower($text1)));
        $words2 = array_unique(explode(' ', mb_strtolower($text2)));
        
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }
    
    /**
     * 성찰 내용 생성
     */
    private function generateReflection($perception, $plan, $actions) {
        $timestamp = date('Y-m-d H:i:s');
        $projects_created = count(array_filter($actions, fn($a) => $a['type'] === 'project_created'));
        
        return "[{$timestamp}] Holon Loop 완료
- 인지된 이벤트: " . count($perception['events']) . "개
- 활성 프로젝트: " . count($perception['projects']) . "개
- 생성된 신규 프로젝트: {$projects_created}개
- 주요 통찰: {$plan['analysis']}
- 다음 단계: 생성된 프로젝트 실행 모니터링 및 피드백 수집";
    }
    
    /**
     * 벡터 DB 저장
     */
    private function saveToVectorDB($agent_id, $data) {
        $embedding_key = "agent_{$agent_id}_holon_" . time();
        $content_hash = md5(json_encode($data));
        
        // 중복 체크
        $existing = $this->db->get_record('wxsperta_embeddings', 
            ['content_hash' => $content_hash]);
        if ($existing) return;
        
        $embedding = new stdClass();
        $embedding->entity_type = 'agent';
        $embedding->entity_id = $agent_id;
        $embedding->embedding_key = $embedding_key;
        $embedding->content_hash = $content_hash;
        $embedding->metadata = json_encode($data);
        $embedding->created_at = time();
        
        $this->db->insert_record('wxsperta_embeddings', $embedding);
        
        // 실제 벡터 DB 연동 (Qdrant/Weaviate 등)
        // TODO: 벡터 DB API 호출
    }
    
    // 헬퍼 메서드들
    private function camelToSnake($camel) {
        return strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($camel)));
    }
    
    private function getAgentUser($agent_id) {
        // 에이전트와 연관된 사용자 찾기
        return $this->db->get_record_sql("
            SELECT DISTINCT u.* 
            FROM {user} u
            JOIN {wxsperta_interactions} i ON i.user_id = u.id
            WHERE i.agent_id = ?
            ORDER BY i.created_at DESC
            LIMIT 1
        ", [$agent_id]);
    }
    
    private function getMetricValue($agent_id, $kpi_name) {
        $metric = $this->db->get_record_sql("
            SELECT kpi_value FROM {wxsperta_metrics}
            WHERE entity_type = 'agent' AND entity_id = ? AND kpi_name = ?
            ORDER BY measured_at DESC
            LIMIT 1
        ", [$agent_id, $kpi_name]);
        
        return $metric ? $metric->kpi_value : 0;
    }
    
    private function summarizeEvents($events) {
        if (empty($events)) return "이벤트 없음";
        
        $summary = [];
        foreach (array_slice($events, 0, 5) as $event) {
            $summary[] = "- {$event->event_type} ({$event->emitter_type}:{$event->emitter_id})";
        }
        return implode("\n", $summary);
    }
    
    private function summarizeProjects($projects) {
        if (empty($projects)) return "활성 프로젝트 없음";
        
        $summary = [];
        foreach ($projects as $project) {
            $summary[] = "- {$project->title} (진행률: {$project->avg_progress}%, 하위: {$project->sub_project_count}개)";
        }
        return implode("\n", $summary);
    }
    
    private function summarizeMetrics($metrics) {
        if (empty($metrics)) return "측정된 메트릭 없음";
        
        $summary = [];
        $grouped = [];
        foreach ($metrics as $metric) {
            $grouped[$metric->kpi_name][] = $metric->kpi_value;
        }
        
        foreach ($grouped as $name => $values) {
            $avg = array_sum($values) / count($values);
            $summary[] = "- {$name}: " . round($avg, 2) . " {$metric->kpi_unit}";
        }
        return implode("\n", $summary);
    }
}

/**
 * 재귀 방지 가드
 */
class RecursionGuard {
    private $db;
    private $max_depth = 10;
    private $call_stack = [];
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function canProceed($entity_id, $entity_type) {
        $key = "{$entity_type}:{$entity_id}";
        
        // 현재 호출 스택에 있는지 확인
        if (in_array($key, $this->call_stack)) {
            // 재귀 감지 - DB에 기록
            $guard = new stdClass();
            $guard->call_chain = json_encode($this->call_stack);
            $guard->depth = count($this->call_stack);
            $guard->max_allowed_depth = $this->max_depth;
            $guard->detected_at = time();
            $this->db->insert_record('wxsperta_recursion_guard', $guard);
            
            return false;
        }
        
        // 깊이 체크
        if (count($this->call_stack) >= $this->max_depth) {
            return false;
        }
        
        // 스택에 추가
        $this->call_stack[] = $key;
        return true;
    }
    
    public function release($entity_id, $entity_type) {
        $key = "{$entity_type}:{$entity_id}";
        $this->call_stack = array_diff($this->call_stack, [$key]);
    }
}

// CLI 실행
if (php_sapi_name() === 'cli') {
    $orchestrator = new LLMOrchestrator();
    
    $action = $argv[1] ?? 'help';
    
    switch ($action) {
        case 'holon':
            $agent_id = $argv[2] ?? 1;
            $result = $orchestrator->executeHolonLoop($agent_id);
            echo $result ? "Holon loop completed for agent $agent_id\n" : "Holon loop failed\n";
            break;
            
        case 'all':
            // 모든 활성 에이전트 실행
            $agents = $DB->get_records('wxsperta_agents', ['is_active' => 1]);
            foreach ($agents as $agent) {
                echo "Processing agent {$agent->id} ({$agent->name})...\n";
                $orchestrator->executeHolonLoop($agent->id);
            }
            break;
            
        default:
            echo "Usage: php llm_orchestrator.php [holon|all] [agent_id]\n";
    }
}
?>