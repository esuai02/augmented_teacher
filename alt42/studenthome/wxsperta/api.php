<?php
require_once("/home/moodle/public_html/moodle/config.php");
require_once("config.php");

global $DB, $USER;
require_login();

// JSON 응답 헤더 설정
header('Content-Type: application/json');

// CORS 헤더 (필요시)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// POST 데이터 읽기
$input = json_decode(file_get_contents('php://input'), true);

// 액션 처리
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'save_agent_properties':
            $result = save_agent_properties($input);
            break;
            
        case 'get_agent_questions':
            $result = get_agent_questions($input);
            break;
            
        case 'save_interaction':
            $result = save_interaction($input);
            break;
            
        case 'get_daily_mission':
            $result = get_daily_mission($input);
            break;
            
        case 'update_agent_priority':
            $result = update_agent_priority($input);
            break;
            
        case 'get_user_profile':
            $result = get_user_profile($input);
            break;
            
        case 'update_user_profile':
            $result = update_user_profile($input);
            break;
            
        case 'trigger_event':
            $result = trigger_event($input);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// API 함수들

function save_agent_properties($data) {
    global $DB;
    
    $agent_id = $data['agent_id'] ?? 0;
    $user_id = $data['user_id'] ?? 0;
    $properties = $data['properties'] ?? [];
    
    if (!$agent_id || !$user_id) {
        throw new Exception('Missing required parameters');
    }
    
    // 에이전트 속성 업데이트
    $agent = $DB->get_record('wxsperta_agents', ['id' => $agent_id]);
    if (!$agent) {
        throw new Exception('Agent not found');
    }
    
    // 속성 업데이트
    foreach ($properties as $key => $value) {
        if (property_exists($agent, $key)) {
            $agent->$key = $value;
        }
    }
    $agent->updated_at = time();
    
    $DB->update_record('wxsperta_agents', $agent);
    
    // 사용자별 우선순위 정보도 업데이트/생성
    $priority_record = $DB->get_record('wxsperta_agent_priorities', [
        'user_id' => $user_id,
        'agent_id' => $agent_id
    ]);
    
    if (!$priority_record) {
        $priority_record = new stdClass();
        $priority_record->user_id = $user_id;
        $priority_record->agent_id = $agent_id;
        $priority_record->priority = DEFAULT_PRIORITY;
        $priority_record->motivator = 'autonomy';
        $priority_record->created_at = time();
        $priority_record->updated_at = time();
        
        $DB->insert_record('wxsperta_agent_priorities', $priority_record);
    }
    
    wxsperta_log("Agent properties saved: agent_id=$agent_id, user_id=$user_id", 'INFO');
    
    return ['success' => true, 'message' => 'Properties saved successfully'];
}

function get_agent_questions($data) {
    global $DB;
    
    $agent_id = $data['agent_id'] ?? 0;
    $question_type = $data['question_type'] ?? 'ask';
    
    if (!$agent_id) {
        throw new Exception('Missing agent_id');
    }
    
    $questions = $DB->get_records('wxsperta_agent_questions', [
        'agent_id' => $agent_id,
        'question_type' => $question_type,
        'is_active' => 1
    ]);
    
    return [
        'success' => true,
        'questions' => array_values($questions)
    ];
}

function save_interaction($data) {
    global $DB, $USER;
    
    $interaction = new stdClass();
    $interaction->user_id = $data['user_id'] ?? $USER->id;
    $interaction->agent_id = $data['agent_id'] ?? 0;
    $interaction->interaction_type = $data['interaction_type'] ?? 'answer';
    $interaction->question_id = $data['question_id'] ?? null;
    $interaction->user_input = $data['user_input'] ?? '';
    $interaction->agent_response = $data['agent_response'] ?? '';
    $interaction->success_score = $data['success_score'] ?? null;
    $interaction->session_id = $data['session_id'] ?? session_id();
    $interaction->created_at = time();
    
    if (!$interaction->agent_id) {
        throw new Exception('Missing agent_id');
    }
    
    $id = $DB->insert_record('wxsperta_interactions', $interaction);
    
    // 에이전트 우선순위 업데이트
    $priority = $DB->get_record('wxsperta_agent_priorities', [
        'user_id' => $interaction->user_id,
        'agent_id' => $interaction->agent_id
    ]);
    
    if ($priority) {
        $priority->last_interaction = time();
        $priority->interaction_count++;
        if ($interaction->success_score !== null) {
            // 성공률 계산 (간단한 이동평균)
            $priority->success_rate = ($priority->success_rate * ($priority->interaction_count - 1) + $interaction->success_score) / $priority->interaction_count;
        }
        $DB->update_record('wxsperta_agent_priorities', $priority);
    }
    
    wxsperta_log("Interaction saved: id=$id, agent_id=$interaction->agent_id", 'INFO');
    
    return [
        'success' => true,
        'interaction_id' => $id
    ];
}

function get_daily_mission($data) {
    global $DB;
    
    $user_id = $data['user_id'] ?? 0;
    $date = $data['date'] ?? date('Y-m-d');
    
    if (!$user_id) {
        throw new Exception('Missing user_id');
    }
    
    // 오늘의 미션 가져오기
    $missions = $DB->get_records_sql("
        SELECT m.*, a.name as agent_name, a.icon as agent_icon
        FROM {wxsperta_daily_missions} m
        JOIN {wxsperta_agents} a ON m.agent_id = a.id
        WHERE m.user_id = ? AND m.mission_date = ?
        ORDER BY m.id
    ", [$user_id, $date]);
    
    // 미션이 없으면 생성
    if (empty($missions)) {
        $missions = generate_daily_missions($user_id, $date);
    }
    
    return [
        'success' => true,
        'missions' => array_values($missions)
    ];
}

function generate_daily_missions($user_id, $date) {
    global $DB;
    
    // 우선순위가 높은 에이전트 3개 선택
    $top_agents = $DB->get_records_sql("
        SELECT a.*, p.priority, p.motivator
        FROM {wxsperta_agents} a
        LEFT JOIN {wxsperta_agent_priorities} p ON a.id = p.agent_id AND p.user_id = ?
        ORDER BY COALESCE(p.priority, 50) DESC, RAND()
        LIMIT 3
    ", [$user_id]);
    
    $missions = [];
    foreach ($top_agents as $agent) {
        $mission = new stdClass();
        $mission->user_id = $user_id;
        $mission->agent_id = $agent->id;
        $mission->mission_date = $date;
        $mission->mission_text = generate_mission_text($agent);
        $mission->mission_type = 'daily';
        $mission->target_value = rand(1, 5);
        $mission->current_value = 0;
        $mission->status = 'pending';
        $mission->reward_points = rand(10, 50);
        $mission->created_at = time();
        
        $mission->id = $DB->insert_record('wxsperta_daily_missions', $mission);
        $mission->agent_name = $agent->name;
        $mission->agent_icon = $agent->icon;
        
        $missions[] = $mission;
    }
    
    return $missions;
}

function generate_mission_text($agent) {
    $templates = [
        'Target' => '오늘 미래의 나에게 편지를 써보세요',
        'Timer' => '주요 작업 3개의 예상 시간을 측정해보세요',
        'TrendingUp' => '어제보다 개선된 점을 3가지 찾아보세요',
        'Heart' => '동기부여가 되는 문장을 5개 수집해보세요',
        'Calendar' => '내일의 일정을 시간 블록으로 계획해보세요'
    ];
    
    return $templates[$agent->icon] ?? '오늘의 목표를 달성해보세요';
}

function update_agent_priority($data) {
    global $DB;
    
    $user_id = $data['user_id'] ?? 0;
    $agent_id = $data['agent_id'] ?? 0;
    $priority_change = $data['priority_change'] ?? 0;
    $event_type = $data['event_type'] ?? '';
    
    if (!$user_id || !$agent_id) {
        throw new Exception('Missing required parameters');
    }
    
    update_agent_priority($agent_id, $event_type);
    
    return ['success' => true];
}

function get_user_profile($data) {
    global $DB;
    
    $user_id = $data['user_id'] ?? 0;
    
    if (!$user_id) {
        throw new Exception('Missing user_id');
    }
    
    $profile = $DB->get_record('wxsperta_user_profiles', ['user_id' => $user_id]);
    
    if (!$profile) {
        // 기본 프로필 생성
        $profile = new stdClass();
        $profile->user_id = $user_id;
        $profile->learning_style = 'visual';
        $profile->interests = json_encode([]);
        $profile->goals = json_encode([]);
        $profile->preferred_motivator = 'autonomy';
        $profile->streak_days = 0;
        $profile->total_interactions = 0;
        $profile->created_at = time();
        $profile->updated_at = time();
        
        $profile->id = $DB->insert_record('wxsperta_user_profiles', $profile);
    }
    
    // JSON 필드 디코딩
    $profile->interests = json_decode($profile->interests ?: '[]');
    $profile->goals = json_decode($profile->goals ?: '[]');
    
    return [
        'success' => true,
        'profile' => $profile
    ];
}

function update_user_profile($data) {
    global $DB;
    
    $user_id = $data['user_id'] ?? 0;
    $updates = $data['updates'] ?? [];
    
    if (!$user_id) {
        throw new Exception('Missing user_id');
    }
    
    $profile = $DB->get_record('wxsperta_user_profiles', ['user_id' => $user_id]);
    
    if (!$profile) {
        throw new Exception('User profile not found');
    }
    
    // 업데이트 적용
    foreach ($updates as $key => $value) {
        if (property_exists($profile, $key)) {
            if ($key === 'interests' || $key === 'goals') {
                $profile->$key = json_encode($value);
            } else {
                $profile->$key = $value;
            }
        }
    }
    
    $profile->updated_at = time();
    $DB->update_record('wxsperta_user_profiles', $profile);
    
    return ['success' => true];
}

function trigger_event($data) {
    global $DB;
    
    $user_id = $data['user_id'] ?? 0;
    $event_type = $data['event_type'] ?? '';
    $event_data = $data['event_data'] ?? [];
    
    if (!$user_id || !$event_type) {
        throw new Exception('Missing required parameters');
    }
    
    // 이벤트 기록
    $event = new stdClass();
    $event->user_id = $user_id;
    $event->event_type = $event_type;
    $event->event_data = json_encode($event_data);
    $event->event_date = date('Y-m-d');
    $event->created_at = time();
    
    // 관련 에이전트 우선순위 업데이트
    $affected_agents = [];
    update_agent_priority_batch($user_id, $event_type, $affected_agents);
    
    $event->triggered_agents = json_encode($affected_agents);
    $event_id = $DB->insert_record('wxsperta_events', $event);
    
    wxsperta_log("Event triggered: type=$event_type, user_id=$user_id", 'INFO');
    
    return [
        'success' => true,
        'event_id' => $event_id,
        'affected_agents' => $affected_agents
    ];
}

function update_agent_priority_batch($user_id, $event_type, &$affected_agents) {
    global $DB;
    
    // 이벤트 타입별 영향받는 에이전트
    $event_agents = [
        'exam' => [3, 4, 6, 7],
        'project' => [2, 7, 11, 13],
        'motivation_low' => [1, 5, 8, 13],
        'achievement' => [3, 12, 15, 20]
    ];
    
    if (isset($event_agents[$event_type])) {
        foreach ($event_agents[$event_type] as $agent_id) {
            update_agent_priority($agent_id, $event_type);
            $affected_agents[] = $agent_id;
        }
    }
}
?>