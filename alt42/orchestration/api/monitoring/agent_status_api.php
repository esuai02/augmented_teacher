<?php
/**
 * Agent Status API
 * 에이전트 현황 데이터 제공 API
 * 
 * @package ALT42\Monitoring
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// Moodle config 로드
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;

// 인증 확인
require_login();

header('Content-Type: application/json; charset=utf-8');

// 에이전트 정보 정의
$agents = [
    ['id' => 'agent01', 'name' => '온보딩', 'category' => 'analysis', 'number' => 1],
    ['id' => 'agent02', 'name' => '시험일정', 'category' => 'analysis', 'number' => 2],
    ['id' => 'agent03', 'name' => '목표분석', 'category' => 'analysis', 'number' => 3],
    ['id' => 'agent04', 'name' => '취약점검사', 'category' => 'analysis', 'number' => 4],
    ['id' => 'agent05', 'name' => '학습감정', 'category' => 'analysis', 'number' => 5],
    ['id' => 'agent06', 'name' => '교사피드백', 'category' => 'support', 'number' => 6],
    ['id' => 'agent07', 'name' => '상호작용타겟팅', 'category' => 'support', 'number' => 7],
    ['id' => 'agent08', 'name' => '침착도', 'category' => 'support', 'number' => 8],
    ['id' => 'agent09', 'name' => '학습관리', 'category' => 'support', 'number' => 9],
    ['id' => 'agent10', 'name' => '개념노트', 'category' => 'support', 'number' => 10],
    ['id' => 'agent11', 'name' => '문제노트', 'category' => 'support', 'number' => 11],
    ['id' => 'agent12', 'name' => '휴식루틴', 'category' => 'support', 'number' => 12],
    ['id' => 'agent13', 'name' => '학습이탈', 'category' => 'support', 'number' => 13],
    ['id' => 'agent14', 'name' => '현재위치', 'category' => 'support', 'number' => 14],
    ['id' => 'agent15', 'name' => '문제재정의', 'category' => 'support', 'number' => 15],
    ['id' => 'agent16', 'name' => '상호작용준비', 'category' => 'execution', 'number' => 16],
    ['id' => 'agent17', 'name' => '잔여활동', 'category' => 'execution', 'number' => 17],
    ['id' => 'agent18', 'name' => '시그너처루틴', 'category' => 'execution', 'number' => 18],
    ['id' => 'agent19', 'name' => '상호작용컨텐츠', 'category' => 'execution', 'number' => 19],
    ['id' => 'agent20', 'name' => '개입준비', 'category' => 'execution', 'number' => 20],
    ['id' => 'agent21', 'name' => '개입실행', 'category' => 'execution', 'number' => 21],
    ['id' => 'agent22', 'name' => '모듈개선', 'category' => 'execution', 'number' => 22]
];

$action = $_GET['action'] ?? 'status';

try {
    switch ($action) {
        case 'status':
            // 전체 에이전트 현황
            $result = getAgentStatus($DB, $agents);
            break;
            
        case 'detail':
            // 특정 에이전트 상세 정보
            $agentId = $_GET['agent_id'] ?? '';
            $result = getAgentDetail($DB, $agentId, $agents);
            break;
            
        case 'events':
            // 에이전트 관련 이벤트 목록
            $agentId = $_GET['agent_id'] ?? '';
            $limit = intval($_GET['limit'] ?? 50);
            $result = getAgentEvents($DB, $agentId, $limit);
            break;
            
        case 'timeline':
            // 이벤트 타임라인
            $hours = intval($_GET['hours'] ?? 24);
            $result = getEventTimeline($DB, $hours);
            break;
            
        case 'stats':
            // 통계 정보
            $period = $_GET['period'] ?? '24h';
            $result = getStats($DB, $period, $agents);
            break;
            
        default:
            throw new Exception("Unknown action: {$action}");
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 에이전트 현황 조회
 */
function getAgentStatus($DB, $agents) {
    $statuses = [];
    
    foreach ($agents as $agent) {
        $agentId = $agent['id'];
        
        // 이벤트 처리 로그에서 통계 조회
        // routing_result JSON에서 agent_id 추출 또는 event_type에서 매칭
        $stats_sql = "SELECT 
            COUNT(*) as total_executions,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as success_count,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as error_count,
            AVG(CASE WHEN status = 'completed' AND processed_at IS NOT NULL 
                THEN TIMESTAMPDIFF(MICROSECOND, created_at, processed_at) / 1000 ELSE NULL END) as avg_duration_ms,
            MAX(created_at) as last_execution_at
        FROM mdl_alt42_event_processing_log
        WHERE (
            event_type LIKE ? 
            OR routing_result LIKE ?
            OR JSON_EXTRACT(routing_result, '$.agent_id') = ?
            OR JSON_EXTRACT(routing_result, '$.selected_agent') = ?
        )
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $pattern = "%{$agentId}%";
        $agentIdQuoted = json_encode($agentId);
        $stats = $DB->get_record_sql($stats_sql, [$pattern, $pattern, $agentIdQuoted, $agentIdQuoted]);
        
        // 최근 오류 조회
        $error_sql = "SELECT 
            id,
            event_type,
            student_id,
            status,
            created_at,
            routing_result
        FROM mdl_alt42_event_processing_log
        WHERE (
            event_type LIKE ? 
            OR routing_result LIKE ?
            OR JSON_EXTRACT(routing_result, '$.agent_id') = ?
            OR JSON_EXTRACT(routing_result, '$.selected_agent') = ?
        )
        AND status = 'failed'
        ORDER BY created_at DESC
        LIMIT 1";
        
        $last_error = $DB->get_record_sql($error_sql, [$pattern, $pattern, $agentIdQuoted, $agentIdQuoted]);
        
        // 상태 결정
        $status = 'normal';
        if ($last_error) {
            $error_time = strtotime($last_error->created_at);
            $hours_ago = (time() - $error_time) / 3600;
            
            if ($hours_ago < 1) {
                $status = 'error';
            } elseif ($hours_ago < 24) {
                $status = 'warning';
            }
        }
        
        // 최근 실행 시간 계산
        $last_execution = $stats->last_execution_at ?? null;
        $last_execution_ago = null;
        if ($last_execution) {
            $last_execution_ago = time() - strtotime($last_execution);
        }
        
        $statuses[] = [
            'id' => $agentId,
            'name' => $agent['name'],
            'category' => $agent['category'],
            'number' => $agent['number'],
            'status' => $status,
            'total_executions' => intval($stats->total_executions ?? 0),
            'success_count' => intval($stats->success_count ?? 0),
            'error_count' => intval($stats->error_count ?? 0),
            'success_rate' => $stats->total_executions > 0 
                ? round(($stats->success_count / $stats->total_executions) * 100, 1) 
                : 0,
            'avg_duration_ms' => round(floatval($stats->avg_duration_ms ?? 0), 2),
            'last_execution_at' => $last_execution,
            'last_execution_ago' => $last_execution_ago,
            'has_recent_error' => $last_error !== false
        ];
    }
    
    return $statuses;
}

/**
 * 에이전트 상세 정보 조회
 */
function getAgentDetail($DB, $agentId, $agents) {
    // 에이전트 정보 찾기
    $agent = null;
    foreach ($agents as $a) {
        if ($a['id'] === $agentId) {
            $agent = $a;
            break;
        }
    }
    
    if (!$agent) {
        throw new Exception("Agent not found: {$agentId}");
    }
    
    $pattern = "%{$agentId}%";
    
    // 실행 로그 (최근 100개로 증가 - 액션별 표시를 위해)
    $logs_sql = "SELECT 
        id,
        event_id,
        event_type,
        student_id,
        scenarios_evaluated,
        scenario_results,
        routing_result,
        priority,
        status,
        processed_at,
        created_at,
        CASE 
            WHEN processed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(MICROSECOND, created_at, processed_at) / 1000 
            ELSE NULL 
        END as duration_ms
    FROM mdl_alt42_event_processing_log
    WHERE (
        event_type LIKE ? 
        OR routing_result LIKE ?
        OR JSON_EXTRACT(routing_result, '$.agent_id') = ?
        OR JSON_EXTRACT(routing_result, '$.selected_agent') = ?
    )
    ORDER BY created_at DESC
    LIMIT 100";
    
    $agentIdQuoted = json_encode($agentId);
    $logs = $DB->get_records_sql($logs_sql, [$pattern, $pattern, $agentIdQuoted, $agentIdQuoted]);
    
    // 성능 통계 (최근 7일)
    $stats_sql = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as executions,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successes,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as errors,
        AVG(CASE WHEN status = 'completed' AND processed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(MICROSECOND, created_at, processed_at) / 1000 ELSE NULL END) as avg_duration_ms,
        MAX(CASE WHEN status = 'completed' AND processed_at IS NOT NULL 
            THEN TIMESTAMPDIFF(MICROSECOND, created_at, processed_at) / 1000 ELSE NULL END) as max_duration_ms
    FROM mdl_alt42_event_processing_log
    WHERE (
        event_type LIKE ? 
        OR routing_result LIKE ?
        OR JSON_EXTRACT(routing_result, '$.agent_id') = ?
        OR JSON_EXTRACT(routing_result, '$.selected_agent') = ?
    )
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC";
    
    $daily_stats = $DB->get_records_sql($stats_sql, [$pattern, $pattern, $agentIdQuoted, $agentIdQuoted]);
    
    // 에러 내역 (최근 20개)
    $errors_sql = "SELECT 
        id,
        event_id,
        event_type,
        student_id,
        status,
        routing_result,
        created_at
    FROM mdl_alt42_event_processing_log
    WHERE (
        event_type LIKE ? 
        OR routing_result LIKE ?
        OR JSON_EXTRACT(routing_result, '$.agent_id') = ?
        OR JSON_EXTRACT(routing_result, '$.selected_agent') = ?
    )
    AND status = 'failed'
    ORDER BY created_at DESC
    LIMIT 20";
    
    $errors = $DB->get_records_sql($errors_sql, [$pattern, $pattern, $agentIdQuoted, $agentIdQuoted]);
    
    // 관련 이벤트 (최근 100개)
    $events_sql = "SELECT 
        id,
        event_id,
        event_type,
        student_id,
        scenarios_evaluated,
        status,
        created_at
    FROM mdl_alt42_event_processing_log
    WHERE (
        event_type LIKE ? 
        OR routing_result LIKE ?
        OR JSON_EXTRACT(routing_result, '$.agent_id') = ?
        OR JSON_EXTRACT(routing_result, '$.selected_agent') = ?
    )
    ORDER BY created_at DESC
    LIMIT 100";
    
    $events = $DB->get_records_sql($events_sql, [$pattern, $pattern, $agentIdQuoted, $agentIdQuoted]);
    
    return [
        'agent' => $agent,
        'execution_logs' => array_values($logs),
        'daily_stats' => array_values($daily_stats),
        'errors' => array_values($errors),
        'related_events' => array_values($events)
    ];
}

/**
 * 에이전트 관련 이벤트 목록 조회
 */
function getAgentEvents($DB, $agentId, $limit) {
    $pattern = "%{$agentId}%";
    
    $sql = "SELECT 
        id,
        event_id,
        event_type,
        student_id,
        scenarios_evaluated,
        scenario_results,
        routing_result,
        priority,
        status,
        created_at,
        processed_at
    FROM mdl_alt42_event_processing_log
    WHERE (
        event_type LIKE ? 
        OR routing_result LIKE ?
        OR JSON_EXTRACT(routing_result, '$.agent_id') = ?
        OR JSON_EXTRACT(routing_result, '$.selected_agent') = ?
    )
    ORDER BY created_at DESC
    LIMIT ?";
    
    $agentIdQuoted = json_encode($agentId);
    $events = $DB->get_records_sql($sql, [$pattern, $pattern, $agentIdQuoted, $agentIdQuoted, $limit]);
    
    return array_values($events);
}

/**
 * 이벤트 타임라인 조회
 */
function getEventTimeline($DB, $hours) {
    $sql = "SELECT 
        id,
        event_id,
        event_type,
        student_id,
        status,
        priority,
        created_at
    FROM mdl_alt42_event_processing_log
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
    ORDER BY created_at DESC
    LIMIT 500";
    
    $events = $DB->get_records_sql($sql, [$hours]);
    
    return array_values($events);
}

/**
 * 통계 정보 조회
 */
function getStats($DB, $period, $agents) {
    $interval_map = [
        '1h' => 'INTERVAL 1 HOUR',
        '24h' => 'INTERVAL 24 HOUR',
        '7d' => 'INTERVAL 7 DAY',
        '30d' => 'INTERVAL 30 DAY'
    ];
    
    $interval = $interval_map[$period] ?? $interval_map['24h'];
    
    // 전체 통계
    $total_sql = "SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_events,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_events,
        AVG(CASE WHEN status = 'completed' THEN TIMESTAMPDIFF(MICROSECOND, created_at, processed_at) / 1000 ELSE NULL END) as avg_duration_ms
    FROM mdl_alt42_event_processing_log
    WHERE created_at >= DATE_SUB(NOW(), {$interval})";
    
    $total_stats = $DB->get_record_sql($total_sql);
    
    // 이벤트 타입별 통계
    $type_sql = "SELECT 
        event_type,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM mdl_alt42_event_processing_log
    WHERE created_at >= DATE_SUB(NOW(), {$interval})
    GROUP BY event_type
    ORDER BY count DESC
    LIMIT 20";
    
    $type_stats = $DB->get_records_sql($type_sql);
    
    return [
        'period' => $period,
        'total' => [
            'events' => intval($total_stats->total_events ?? 0),
            'completed' => intval($total_stats->completed_events ?? 0),
            'failed' => intval($total_stats->failed_events ?? 0),
            'avg_duration_ms' => round(floatval($total_stats->avg_duration_ms ?? 0), 2)
        ],
        'by_type' => array_values($type_stats)
    ];
}

