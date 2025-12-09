<?php
/**
 * Event Bus System for Holonic WXSPERTA
 * 실시간 이벤트 처리 및 배포
 */

require_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");

class EventBus {
    private $db;
    private $redis;
    private $max_retry = 3;
    
    public function __construct() {
        global $DB;
        $this->db = $DB;
        
        // Redis 연결 (옵션)
        try {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            $this->redis = null;
            wxsperta_log("Redis connection failed: " . $e->getMessage(), 'WARNING');
        }
    }
    
    /**
     * 이벤트 발행
     */
    public function emit($event_type, $emitter_type, $emitter_id, $payload, $target = null) {
        $event = new stdClass();
        $event->event_type = $event_type;
        $event->emitter_type = $emitter_type;
        $event->emitter_id = $emitter_id;
        $event->payload = is_array($payload) ? json_encode($payload) : $payload;
        $event->priority = $this->calculatePriority($event_type);
        $event->status = 'pending';
        $event->created_at = time();
        
        if ($target) {
            $event->target_type = $target['type'];
            $event->target_id = $target['id'];
        }
        
        // DB에 저장
        $event_id = $this->db->insert_record('wxsperta_event_bus', $event);
        
        // Redis로 실시간 발행 (가능한 경우)
        if ($this->redis) {
            $this->redis->publish('wxsperta:events', json_encode([
                'id' => $event_id,
                'type' => $event_type,
                'emitter' => "$emitter_type:$emitter_id",
                'timestamp' => time()
            ]));
        }
        
        wxsperta_log("Event emitted: $event_type from $emitter_type:$emitter_id", 'INFO');
        
        return $event_id;
    }
    
    /**
     * 이벤트 구독 및 처리
     */
    public function processEvents($limit = 10) {
        // 대기 중인 이벤트 가져오기
        $events = $this->db->get_records_sql("
            SELECT * FROM {wxsperta_event_bus}
            WHERE status = 'pending' 
            AND retry_count < max_retries
            ORDER BY priority DESC, created_at ASC
            LIMIT ?
        ", [$limit]);
        
        $processed = 0;
        foreach ($events as $event) {
            if ($this->processEvent($event)) {
                $processed++;
            }
        }
        
        return $processed;
    }
    
    /**
     * 개별 이벤트 처리
     */
    private function processEvent($event) {
        // 상태를 processing으로 변경
        $this->db->set_field('wxsperta_event_bus', 'status', 'processing', ['id' => $event->id]);
        
        try {
            $payload = json_decode($event->payload, true);
            
            // 이벤트 타입별 처리
            switch ($event->event_type) {
                case 'student_question':
                    $this->handleStudentQuestion($event, $payload);
                    break;
                    
                case 'progress_update':
                    $this->handleProgressUpdate($event, $payload);
                    break;
                    
                case 'emotion_detected':
                    $this->handleEmotionDetected($event, $payload);
                    break;
                    
                case 'project_created':
                    $this->handleProjectCreated($event, $payload);
                    break;
                    
                case 'agent_update_request':
                    $this->handleAgentUpdateRequest($event, $payload);
                    break;
                    
                case 'metric_changed':
                    $this->handleMetricChanged($event, $payload);
                    break;
                    
                default:
                    // 브로드캐스트 이벤트
                    $this->broadcastToAgents($event, $payload);
            }
            
            // 처리 완료
            $this->db->set_field('wxsperta_event_bus', 'status', 'processed', ['id' => $event->id]);
            $this->db->set_field('wxsperta_event_bus', 'processed_at', time(), ['id' => $event->id]);
            
            return true;
            
        } catch (Exception $e) {
            // 재시도 카운트 증가
            $event->retry_count++;
            $this->db->update_record('wxsperta_event_bus', $event);
            
            wxsperta_log("Event processing failed: " . $e->getMessage(), 'ERROR');
            
            if ($event->retry_count >= $event->max_retries) {
                $this->db->set_field('wxsperta_event_bus', 'status', 'failed', ['id' => $event->id]);
            } else {
                $this->db->set_field('wxsperta_event_bus', 'status', 'pending', ['id' => $event->id]);
            }
            
            return false;
        }
    }
    
    /**
     * 학생 질문 처리
     */
    private function handleStudentQuestion($event, $payload) {
        // 관련 에이전트 찾기
        $agent_id = $payload['agent_id'] ?? null;
        $question = $payload['question'] ?? '';
        $user_id = $payload['user_id'] ?? 0;
        
        if (!$agent_id || !$question) return;
        
        // 질문 분석을 위한 이벤트 생성
        $this->emit('analyze_question', 'system', 0, [
            'agent_id' => $agent_id,
            'question' => $question,
            'user_id' => $user_id,
            'context' => $payload['context'] ?? []
        ], ['type' => 'agent', 'id' => $agent_id]);
        
        // 감정 분석 트리거
        $this->emit('analyze_emotion', 'system', 0, [
            'text' => $question,
            'user_id' => $user_id
        ]);
    }
    
    /**
     * 진행률 업데이트 처리
     */
    private function handleProgressUpdate($event, $payload) {
        $project_id = $payload['project_id'] ?? null;
        $progress = $payload['progress'] ?? 0;
        
        if (!$project_id) return;
        
        // 상위 프로젝트로 진행률 전파
        $this->propagateProgress($project_id, $progress);
        
        // 메트릭 업데이트
        $this->updateMetric('project', $project_id, 'progress', $progress, '%');
    }
    
    /**
     * 감정 감지 처리
     */
    private function handleEmotionDetected($event, $payload) {
        $user_id = $payload['user_id'] ?? 0;
        $emotion = $payload['emotion'] ?? 'neutral';
        $intensity = $payload['intensity'] ?? 0.5;
        
        // 관련 에이전트에게 알림
        if ($emotion === 'frustrated' && $intensity > 0.7) {
            // 동기 엔진(5번)과 내면 브랜딩(8번) 활성화
            $this->emit('urgent_support_needed', 'system', 0, [
                'user_id' => $user_id,
                'emotion' => $emotion,
                'intensity' => $intensity
            ], ['type' => 'agent', 'id' => 5]);
            
            $this->emit('urgent_support_needed', 'system', 0, [
                'user_id' => $user_id,
                'emotion' => $emotion,
                'intensity' => $intensity
            ], ['type' => 'agent', 'id' => 8]);
        }
    }
    
    /**
     * 프로젝트 생성 처리
     */
    private function handleProjectCreated($event, $payload) {
        $project_id = $payload['project_id'] ?? null;
        $parent_agent_id = $payload['agent_id'] ?? null;
        
        if (!$project_id || !$parent_agent_id) return;
        
        // 실행 파이프라인(11번)에게 알림
        $this->emit('new_project_assigned', 'system', 0, [
            'project_id' => $project_id,
            'parent_agent_id' => $parent_agent_id
        ], ['type' => 'agent', 'id' => 11]);
        
        // 일일 사령부(7번)에게 알림
        $this->emit('schedule_project', 'system', 0, [
            'project_id' => $project_id
        ], ['type' => 'agent', 'id' => 7]);
    }
    
    /**
     * 에이전트 업데이트 요청 처리
     */
    private function handleAgentUpdateRequest($event, $payload) {
        $agent_id = $payload['agent_id'] ?? null;
        $updates = $payload['updates'] ?? [];
        $user_id = $payload['user_id'] ?? 0;
        
        if (!$agent_id || empty($updates)) return;
        
        // 학생 승인 요청 생성
        $approval = new stdClass();
        $approval->user_id = $user_id;
        $approval->request_type = 'agent_update';
        $approval->entity_type = 'agent';
        $approval->entity_id = $agent_id;
        $approval->change_description = $payload['description'] ?? 'Agent property update';
        $approval->old_value = json_encode($payload['old_values'] ?? []);
        $approval->new_value = json_encode($updates);
        $approval->requested_by_agent_id = $agent_id;
        $approval->status = 'pending';
        $approval->requested_at = time();
        $approval->expires_at = time() + (24 * 3600); // 24시간 후 만료
        
        $approval_id = $this->db->insert_record('wxsperta_approval_requests', $approval);
        
        // 사용자에게 알림
        $this->emit('approval_requested', 'system', 0, [
            'approval_id' => $approval_id,
            'type' => 'agent_update',
            'agent_name' => $this->getAgentName($agent_id),
            'description' => $approval->change_description
        ], ['type' => 'user', 'id' => $user_id]);
    }
    
    /**
     * 메트릭 변경 처리
     */
    private function handleMetricChanged($event, $payload) {
        $entity_type = $payload['entity_type'] ?? '';
        $entity_id = $payload['entity_id'] ?? 0;
        $kpi_name = $payload['kpi_name'] ?? '';
        $value = $payload['value'] ?? 0;
        
        // 상위 에이전트/프로젝트로 롤업
        if ($entity_type === 'project') {
            $this->rollupMetrics($entity_id);
        }
    }
    
    /**
     * 에이전트들에게 브로드캐스트
     */
    private function broadcastToAgents($event, $payload) {
        // 활성 에이전트 목록 가져오기
        $agents = $this->db->get_records('wxsperta_agents', ['is_active' => 1]);
        
        foreach ($agents as $agent) {
            // 각 에이전트의 관심사에 따라 필터링
            if ($this->isAgentInterested($agent, $event->event_type)) {
                $this->emit('broadcast_received', 'system', 0, [
                    'original_event' => $event->event_type,
                    'payload' => $payload
                ], ['type' => 'agent', 'id' => $agent->id]);
            }
        }
    }
    
    /**
     * 진행률 상위 전파
     */
    private function propagateProgress($project_id, $progress) {
        $project = $this->db->get_record('wxsperta_projects', ['id' => $project_id]);
        if (!$project || !$project->parent_project_id) return;
        
        // 형제 프로젝트들의 진행률 평균 계산
        $siblings = $this->db->get_records_sql("
            SELECT AVG(COALESCE(m.kpi_value, 0)) as avg_progress
            FROM {wxsperta_projects} p
            LEFT JOIN {wxsperta_metrics} m ON m.entity_type = 'project' 
                AND m.entity_id = p.id AND m.kpi_name = 'progress'
            WHERE p.parent_project_id = ?
        ", [$project->parent_project_id]);
        
        $avg_progress = $siblings[0]->avg_progress ?? 0;
        
        // 상위 프로젝트 업데이트
        $this->updateMetric('project', $project->parent_project_id, 'progress', $avg_progress, '%');
        
        // 재귀적으로 상위 전파
        $this->propagateProgress($project->parent_project_id, $avg_progress);
    }
    
    /**
     * 메트릭 롤업
     */
    private function rollupMetrics($project_id) {
        // WITH RECURSIVE 쿼리로 하위 프로젝트 메트릭 집계
        $sql = "
            WITH RECURSIVE project_tree AS (
                SELECT id FROM {wxsperta_projects} WHERE id = ?
                UNION ALL
                SELECT p.id FROM {wxsperta_projects} p
                INNER JOIN project_tree pt ON p.parent_project_id = pt.id
            )
            SELECT 
                kpi_name,
                AVG(kpi_value) as avg_value,
                SUM(kpi_value) as sum_value,
                COUNT(*) as count_value
            FROM {wxsperta_metrics} m
            WHERE m.entity_type = 'project' 
            AND m.entity_id IN (SELECT id FROM project_tree)
            GROUP BY kpi_name
        ";
        
        $metrics = $this->db->get_records_sql($sql, [$project_id]);
        
        foreach ($metrics as $metric) {
            // 상위 프로젝트의 에이전트에게 알림
            $project = $this->db->get_record('wxsperta_projects', ['id' => $project_id]);
            if ($project) {
                $this->emit('metrics_rolled_up', 'system', 0, [
                    'project_id' => $project_id,
                    'metrics' => $metric
                ], ['type' => 'agent', 'id' => $project->agent_owner_id]);
            }
        }
    }
    
    /**
     * 메트릭 업데이트
     */
    private function updateMetric($entity_type, $entity_id, $kpi_name, $value, $unit = '') {
        $metric = new stdClass();
        $metric->entity_type = $entity_type;
        $metric->entity_id = $entity_id;
        $metric->kpi_name = $kpi_name;
        $metric->kpi_value = $value;
        $metric->kpi_unit = $unit;
        $metric->measured_at = time();
        
        $this->db->insert_record('wxsperta_metrics', $metric);
        
        // 변경 이벤트 발행
        $this->emit('metric_changed', 'system', 0, [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'kpi_name' => $kpi_name,
            'value' => $value
        ]);
    }
    
    /**
     * 우선순위 계산
     */
    private function calculatePriority($event_type) {
        $priorities = [
            'student_question' => 100,
            'urgent_support_needed' => 90,
            'approval_requested' => 80,
            'project_created' => 70,
            'progress_update' => 60,
            'metric_changed' => 50,
            'broadcast_received' => 40
        ];
        
        return $priorities[$event_type] ?? 50;
    }
    
    /**
     * 에이전트 관심사 확인
     */
    private function isAgentInterested($agent, $event_type) {
        // 에이전트별 관심 이벤트 매핑
        $interests = [
            1 => ['student_question', 'goal_setting'],  // 시간 수정체
            5 => ['emotion_detected', 'motivation_low'], // 동기 엔진
            7 => ['schedule_project', 'daily_planning'], // 일일 사령부
            11 => ['new_project_assigned', 'task_update'], // 실행 파이프라인
            15 => ['metrics_rolled_up', 'weekly_report']  // CEO
        ];
        
        $agent_interests = $interests[$agent->id] ?? [];
        return in_array($event_type, $agent_interests) || 
               strpos($event_type, 'broadcast') !== false;
    }
    
    /**
     * 에이전트 이름 가져오기
     */
    private function getAgentName($agent_id) {
        $agent = $this->db->get_record('wxsperta_agents', ['id' => $agent_id]);
        return $agent ? $agent->name : "Agent #$agent_id";
    }
}

// CLI 실행을 위한 처리
if (php_sapi_name() === 'cli') {
    $eventBus = new EventBus();
    
    // 명령행 인자 처리
    $action = $argv[1] ?? 'process';
    
    switch ($action) {
        case 'process':
            $limit = $argv[2] ?? 10;
            $processed = $eventBus->processEvents($limit);
            echo "Processed $processed events\n";
            break;
            
        case 'emit':
            $type = $argv[2] ?? 'test';
            $payload = $argv[3] ?? '{}';
            $id = $eventBus->emit($type, 'cli', 0, $payload);
            echo "Event emitted with ID: $id\n";
            break;
            
        default:
            echo "Usage: php event_bus.php [process|emit] [args...]\n";
    }
}
?>