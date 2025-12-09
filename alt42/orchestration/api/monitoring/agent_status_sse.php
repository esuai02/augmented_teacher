<?php
/**
 * Agent Status SSE (Server-Sent Events)
 * 실시간 에이전트 현황 업데이트 스트림
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

// SSE 헤더 설정
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Nginx 버퍼링 비활성화

// 무한 실행 방지 (최대 1시간)
set_time_limit(3600);

// 에이전트 정보 정의 (agent_status_api.php와 동일)
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

// 업데이트 간격 (초)
$update_interval = intval($_GET['interval'] ?? 5); // 기본 5초
$update_interval = max(1, min(300, $update_interval)); // 1~300초 제한

// 마지막 이벤트 ID 추적
$last_event_id = intval($_GET['last_id'] ?? 0);

// 연결 확인 메시지 전송
echo "data: " . json_encode(['type' => 'connected', 'timestamp' => date('c')]) . "\n\n";
flush();

$iteration = 0;
$max_iterations = 7200; // 최대 2시간 (5초 간격 기준)

while ($iteration < $max_iterations) {
    // 클라이언트 연결 확인
    if (connection_aborted()) {
        break;
    }
    
    try {
        // 에이전트 현황 조회
        $statuses = [];
        
        foreach ($agents as $agent) {
            $agentId = $agent['id'];
            $pattern = "%{$agentId}%";
            
            // 최근 실행 통계
            $stats_sql = "SELECT 
                COUNT(*) as total_executions,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as error_count,
                MAX(created_at) as last_execution_at
            FROM mdl_alt42_event_processing_log
            WHERE (
                event_type LIKE ? 
                OR routing_result LIKE ?
                OR JSON_EXTRACT(routing_result, '$.agent_id') = ?
                OR JSON_EXTRACT(routing_result, '$.selected_agent') = ?
            )
            AND id > ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            
            $agentIdQuoted = json_encode($agentId);
            $stats = $DB->get_record_sql($stats_sql, [$pattern, $pattern, $agentIdQuoted, $agentIdQuoted, $last_event_id]);
            
            if ($stats && $stats->total_executions > 0) {
                $statuses[] = [
                    'id' => $agentId,
                    'name' => $agent['name'],
                    'category' => $agent['category'],
                    'number' => $agent['number'],
                    'new_executions' => intval($stats->total_executions),
                    'new_successes' => intval($stats->success_count),
                    'new_errors' => intval($stats->error_count),
                    'last_execution_at' => $stats->last_execution_at
                ];
                
                // 마지막 이벤트 ID 업데이트
                $last_id_sql = "SELECT MAX(id) as max_id FROM mdl_alt42_event_processing_log 
                               WHERE (
                                   event_type LIKE ? 
                                   OR routing_result LIKE ?
                                   OR JSON_EXTRACT(routing_result, '$.agent_id') = ?
                                   OR JSON_EXTRACT(routing_result, '$.selected_agent') = ?
                               )";
                $last_id_result = $DB->get_record_sql($last_id_sql, [$pattern, $pattern, $agentIdQuoted, $agentIdQuoted]);
                if ($last_id_result && $last_id_result->max_id > $last_event_id) {
                    $last_event_id = $last_id_result->max_id;
                }
            }
        }
        
        // 변경사항이 있으면 전송
        if (!empty($statuses)) {
            echo "data: " . json_encode([
                'type' => 'update',
                'agents' => $statuses,
                'timestamp' => date('c')
            ], JSON_UNESCAPED_UNICODE) . "\n\n";
            flush();
        } else {
            // 하트비트 전송 (연결 유지)
            echo "data: " . json_encode([
                'type' => 'heartbeat',
                'timestamp' => date('c')
            ]) . "\n\n";
            flush();
        }
        
    } catch (\Exception $e) {
        echo "data: " . json_encode([
            'type' => 'error',
            'message' => $e->getMessage(),
            'timestamp' => date('c')
        ]) . "\n\n";
        flush();
    }
    
    // 대기
    sleep($update_interval);
    $iteration++;
}

// 연결 종료 메시지
echo "data: " . json_encode(['type' => 'closed', 'timestamp' => date('c')]) . "\n\n";
flush();

