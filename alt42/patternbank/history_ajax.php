<?php
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 출력 방지
error_reporting(0);
ini_set('display_errors', 0);

// JSON 응답 헤더
header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
if (!isloggedin()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'get_history') {
    try {
        $period = $_POST['period'] ?? 'week';
        
        // 디버깅 로그
        error_log("History request - Period: $period");
        
        // 기간 계산
        $now = time();
        switch ($period) {
            case 'today':
                $start_time = strtotime('today');
                break;
            case 'week':
                $start_time = strtotime('-1 week');
                break;
            case 'month':
                $start_time = strtotime('-1 month');
                break;
            case 'quarter':
                $start_time = strtotime('-3 months');
                break;
            default:
                $start_time = strtotime('-1 week');
        }
        
        error_log("Start time: " . date('Y-m-d H:i:s', $start_time));
        
        // 테이블 존재 확인
        try {
            $table_check = $DB->get_record_sql("SELECT COUNT(*) as cnt FROM mdl_abessi_patternbank WHERE 1=0");
        } catch (Exception $e) {
            error_log("Table check error: " . $e->getMessage());
            // 테이블명이 다를 수 있으므로 다른 방법 시도
        }
        
        // 패턴뱅크 문제들 조회
        $sql = "SELECT p.*, u.firstname, u.lastname, 
                       ic.title as content_title, ic.pageicontent as content_text
                FROM mdl_abessi_patternbank p
                JOIN mdl_user u ON p.authorid = u.id
                LEFT JOIN mdl_icontent_pages ic ON p.cntid = ic.id AND p.cnttype = 1
                WHERE p.timecreated >= :start_time
                ORDER BY p.cntid, p.timecreated DESC";
        
        error_log("SQL Query: " . $sql);
        
        $problems = $DB->get_records_sql($sql, ['start_time' => $start_time]);
        
        error_log("Found " . count($problems) . " problems");
        
        // 데이터가 없는 경우 빈 결과 반환
        if (empty($problems)) {
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_problems' => 0,
                    'total_contents' => 0,
                    'similar_count' => 0,
                    'modified_count' => 0
                ],
                'contents' => []
            ]);
            exit;
        }
        
        // 통계 계산
        $stats = [
            'total_problems' => count($problems),
            'total_contents' => 0,
            'similar_count' => 0,
            'modified_count' => 0
        ];
        
        // 콘텐츠별로 그룹화
        $contents_map = [];
        foreach ($problems as $problem) {
            $content_id = $problem->cntid;
            
            // 통계 업데이트
            if ($problem->type === 'modified') {
                $stats['modified_count']++;
            } else {
                $stats['similar_count']++;
            }
            
            // 콘텐츠별 그룹화
            if (!isset($contents_map[$content_id])) {
                $contents_map[$content_id] = [
                    'content_id' => $content_id,
                    'content_title' => $problem->content_title ?: "콘텐츠 #{$content_id}",
                    'content_type' => $problem->cnttype,
                    'problems' => []
                ];
            }
            
            // 문제 정보 추가
            $contents_map[$content_id]['problems'][] = [
                'id' => $problem->id,
                'question' => $problem->question,
                'type' => $problem->type ?? 'similar',
                'author_name' => $problem->firstname . ' ' . $problem->lastname,
                'timecreated' => $problem->timecreated
            ];
        }
        
        // 콘텐츠 수 계산
        $stats['total_contents'] = count($contents_map);
        
        // 배열로 변환
        $contents = array_values($contents_map);
        
        // 콘텐츠를 최신 문제 기준으로 정렬
        usort($contents, function($a, $b) {
            $a_latest = max(array_column($a['problems'], 'timecreated'));
            $b_latest = max(array_column($b['problems'], 'timecreated'));
            return $b_latest - $a_latest;
        });
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'contents' => $contents
        ]);
        
    } catch (Exception $e) {
        error_log("History error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 문제 상세 정보 조회 (필요시 사용)
if ($action === 'get_problem_detail') {
    try {
        $problem_id = intval($_POST['problem_id']);
        
        $problem = $DB->get_record_sql("
            SELECT p.*, u.firstname, u.lastname
            FROM mdl_abessi_patternbank p
            JOIN mdl_user u ON p.authorid = u.id
            WHERE p.id = :id
        ", ['id' => $problem_id]);
        
        if ($problem) {
            echo json_encode([
                'success' => true,
                'problem' => [
                    'id' => $problem->id,
                    'question' => $problem->question,
                    'solution' => $problem->solution,
                    'inputanswer' => $problem->inputanswer,
                    'type' => $problem->type ?? 'similar',
                    'author_name' => $problem->firstname . ' ' . $problem->lastname,
                    'timecreated' => $problem->timecreated
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Problem not found'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 잘못된 액션
echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>