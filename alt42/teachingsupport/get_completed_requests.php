<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$teacherid = required_param('teacherid', PARAM_INT);

try {
    // 디버깅 로그
    error_log("get_completed_requests.php - teacherid: $teacherid, USER->id: {$USER->id}");
    
    // 테이블 존재 확인
    if (!$DB->get_manager()->table_exists('ktm_teaching_interactions')) {
        throw new Exception('ktm_teaching_interactions 테이블이 존재하지 않습니다.');
    }
    
    // 권한 확인 - 본인이거나 관리자만 접근 가능
    $context = context_system::instance();
    if ($teacherid != $USER->id && !has_capability('moodle/site:config', $context)) {
        throw new Exception('접근 권한이 없습니다.');
    }

    // 먼저 전체 완료된 항목 수 확인 (디버깅용)
    $debug_sql = "SELECT COUNT(*) as total FROM {ktm_teaching_interactions} 
                  WHERE (
                      LOWER(TRIM(COALESCE(status, ''))) IN ('completed', 'complete', 'sent', 'finished', 'done')
                      OR (solution_text IS NOT NULL AND solution_text != '' AND LENGTH(TRIM(solution_text)) > 0)
                  )
                  AND (
                      teacherid = ? 
                      OR teacherid = 0 
                      OR teacherid IS NULL
                  )";
    $debug_count = $DB->count_records_sql($debug_sql, array($teacherid));
    error_log("get_completed_requests.php - Total completed records for teacher $teacherid (including NULL/0): $debug_count");
    
    // teacherid가 정확히 일치하는 항목만 카운트
    $debug_sql_exact = "SELECT COUNT(*) as total FROM {ktm_teaching_interactions} 
                        WHERE (
                            LOWER(TRIM(COALESCE(status, ''))) IN ('completed', 'complete', 'sent', 'finished', 'done')
                            OR (solution_text IS NOT NULL AND solution_text != '' AND LENGTH(TRIM(solution_text)) > 0)
                        )
                        AND teacherid = ?";
    $debug_count_exact = $DB->count_records_sql($debug_sql_exact, array($teacherid));
    error_log("get_completed_requests.php - Total completed records for teacher $teacherid (exact match): $debug_count_exact");
    
    // 완료된 풀이요청 가져오기
    // 조건: solution_text가 있거나 status가 완료 상태인 경우
    // teacherid가 정확히 일치하는 경우만 가져오기
    $sql = "SELECT ti.*, u.firstname, u.lastname
            FROM {ktm_teaching_interactions} ti
            JOIN {user} u ON ti.userid = u.id
            WHERE (
                LOWER(TRIM(COALESCE(ti.status, ''))) IN ('completed', 'complete', 'sent', 'finished', 'done')
                OR (ti.solution_text IS NOT NULL AND ti.solution_text != '' AND LENGTH(TRIM(ti.solution_text)) > 0)
            )
            AND ti.teacherid = ?
            AND ti.timecreated > ?
            ORDER BY COALESCE(ti.timemodified, ti.timecreated) DESC, ti.timecreated DESC";
    
    $params = array(
        $teacherid,
        time() - (7 * 24 * 3600) // 최근 1주일
    );
    
    error_log("get_completed_requests.php - SQL: " . $sql);
    error_log("get_completed_requests.php - Params: " . json_encode($params));
    
    try {
        // Moodle의 표준 방식으로 LIMIT 적용
        $requests = $DB->get_records_sql($sql, $params, 0, 50); // 50개로 증가
        error_log("get_completed_requests.php - Found " . count($requests) . " completed requests");
        
        // 디버깅: 각 요청의 상태 확인
        foreach ($requests as $req) {
            error_log("Completed Request ID: {$req->id}, Status: [{$req->status}], TeacherID: {$req->teacherid}, Solution: " . (empty($req->solution_text) ? 'empty' : 'exists (' . strlen($req->solution_text) . ' chars)'));
        }
    } catch (dml_exception $e) {
        error_log("get_completed_requests.php - SQL Error: " . $e->getMessage());
        error_log("get_completed_requests.php - SQL: " . $sql);
        error_log("get_completed_requests.php - Params: " . json_encode($params));
        error_log("get_completed_requests.php - Debug Info: " . $e->debuginfo);
        throw new Exception('데이터베이스 읽기 오류: ' . $e->getMessage());
    }
    
    // 결과 포맷팅
    $results = array();
    foreach ($requests as $request) {
        // 완료 시간 확인
        $completedTime = $request->timemodified ?? $request->timecreated;
        
        $results[] = array(
            'id' => $request->id,
            'studentId' => $request->userid,
            'studentName' => fullname($request),
            'problemType' => $request->problem_type ?? '',
            'problemImage' => $request->problem_image ?? '',
            'problemText' => $request->problem_text ?? '',
            'status' => $request->status ?? 'completed',
            'timecreated' => $request->timecreated,
            'timemodified' => $completedTime,
            'timeAgo' => time_ago($completedTime),
            'hasSolution' => !empty($request->solution_text)
        );
    }

    error_log("get_completed_requests.php - Returning " . count($results) . " formatted results");
    
    // 디버깅 정보 추가
    $debug_info = array(
        'teacherid' => $teacherid,
        'user_id' => $USER->id,
        'sql_params' => $params,
        'total_found' => count($requests),
        'total_formatted' => count($results),
        'time_range_days' => 7
    );
    
    echo json_encode(array(
        'success' => true,
        'requests' => $results,
        'total' => count($results),
        'debug' => $debug_info
    ), JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("get_completed_requests.php - Error: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}

// 시간 경과 계산 함수
function time_ago($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return '방금 전';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '분 전';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '시간 전';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . '일 전';
    } else {
        return date('Y-m-d H:i', $timestamp);
    }
}
?>

