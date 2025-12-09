<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

// 간단하고 안전한 매개변수 처리
$teacherid = isset($_GET['teacherid']) ? (int)$_GET['teacherid'] : $USER->id;
$studentid = isset($_GET['studentid']) ? (int)$_GET['studentid'] : 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$perpage = isset($_GET['perpage']) ? (int)$_GET['perpage'] : 20;

// 필터 값 검증
$allowed_filters = array('all', 'pending', 'completed', 'in_progress');
if (!in_array($filter, $allowed_filters)) {
    $filter = 'all';
}

try {
    // 디버깅 로그 추가
    error_log("get_teacher_interactions.php - 선생님 상호작용 조회 시작: TeacherId: $teacherid, StudentId: $studentid, Filter: $filter");
    
    // 매개변수 검증
    if ($teacherid <= 0) {
        throw new Exception('유효하지 않은 선생님 ID입니다.');
    }
    
    // 테이블 존재 확인
    if (!$DB->get_manager()->table_exists('ktm_teaching_interactions')) {
        throw new Exception('ktm_teaching_interactions 테이블이 존재하지 않습니다.');
    }
    
    // 선생님 정보 확인
    $teacher = $DB->get_record('user', array('id' => $teacherid));
    if (!$teacher) {
        throw new Exception('선생님 정보를 찾을 수 없습니다.');
    }
    
    // WHERE 조건 구성 - userid가 teacherid인 경우 조회
    $where_conditions = array("teacherid = ?");
    $params = array($teacherid);
    
    // 학생 필터링
    if ($studentid > 0) {
        $where_conditions[] = "userid = ?";
        $params[] = $studentid;
    }
    
    // 상태 필터링
    if ($filter !== 'all') {
        if ($filter === 'pending') {
            $where_conditions[] = "status = 'pending'";
        } elseif ($filter === 'completed') {
            $where_conditions[] = "status = 'completed'";
        } elseif ($filter === 'in_progress') {
            $where_conditions[] = "status = 'in_progress'";
        }
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // 메인 쿼리 - 학생 정보와 함께
    $sql = "SELECT ti.*, 
                   u.firstname, u.lastname,
                   CONCAT(u.firstname, ' ', u.lastname) as student_name
            FROM {ktm_teaching_interactions} ti 
            JOIN {user} u ON ti.userid = u.id
            WHERE $where_clause
            ORDER BY ti.timecreated DESC";
    
    // 페이징 적용
    $offset = $page * $perpage;
    
    // 디버깅을 위한 로그
    error_log("get_teacher_interactions.php - SQL: $sql");
    error_log("get_teacher_interactions.php - Params: " . json_encode($params));
    
    $interactions = $DB->get_records_sql($sql, $params, $offset, $perpage);
    
    error_log("get_teacher_interactions.php - Found " . count($interactions) . " interactions");
    
    // 전체 상호작용 수 계산
    $sql_count = "SELECT COUNT(*) FROM {ktm_teaching_interactions} ti WHERE $where_clause";
    $total_count = $DB->count_records_sql($sql_count, $params);
    error_log("get_teacher_interactions.php - Total interactions: $total_count");
    
    // 상호작용 데이터 포맷팅
    $formatted_interactions = array();
    foreach ($interactions as $interaction) {
        $formatted_interactions[] = array(
            'id' => $interaction->id,
            'userid' => $interaction->userid,
            'student_name' => $interaction->student_name,
            'teacherid' => $interaction->teacherid,
            'teacher_name' => fullname($teacher),
            'problem_text' => $interaction->problem_text,
            'problem_image' => $interaction->problem_image,
            'problem_type' => $interaction->problem_type,
            'solution_text' => $interaction->solution_text,
            'audio_url' => $interaction->audio_url,
            'status' => $interaction->status,
            'modification_prompt' => $interaction->modification_prompt ?? '',
            'timecreated' => $interaction->timecreated,
            'timemodified' => $interaction->timemodified
        );
    }
    
    // 통계 데이터 계산
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
                  FROM {ktm_teaching_interactions} ti 
                  WHERE teacherid = ?";
    
    $stats_params = array($teacherid);
    if ($studentid > 0) {
        $stats_sql .= " AND userid = ?";
        $stats_params[] = $studentid;
    }
    
    $stats_result = $DB->get_record_sql($stats_sql, $stats_params);
    $stats = array(
        'total' => $stats_result->total ?? 0,
        'completed' => $stats_result->completed ?? 0,
        'pending' => $stats_result->pending ?? 0,
        'in_progress' => $stats_result->in_progress ?? 0
    );
    
    // 페이지네이션 데이터
    $total_pages = ceil($total_count / $perpage);
    $pagination = array(
        'current_page' => $page,
        'total_pages' => $total_pages,
        'per_page' => $perpage,
        'total_items' => $total_count
    );
    
    echo json_encode(array(
        'success' => true,
        'interactions' => $formatted_interactions,
        'stats' => $stats,
        'pagination' => $pagination,
        'teacher' => array(
            'id' => $teacher->id,
            'name' => fullname($teacher),
            'email' => $teacher->email
        )
    ));
    
} catch (Exception $e) {
    error_log("get_teacher_interactions.php - Error: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>