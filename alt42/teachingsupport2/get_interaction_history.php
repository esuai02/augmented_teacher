<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$userid = $_GET['userid'] ?? null;
$studentid = $_GET['studentid'] ?? null;
$page = $_GET['page'] ?? 0;
$perpage = $_GET['perpage'] ?? 20;
$filter = $_GET['filter'] ?? 'all';

try {
    // 디버깅 로그 추가
    error_log("get_interaction_history.php - 상호작용 히스토리 조회 시작: UserId: $userid, StudentId: $studentid, Page: $page, PerPage: $perpage, Filter: $filter");
    
    // 선생님 정보 확인
    if (!$userid) {
        throw new Exception('선생님 ID가 지정되지 않았습니다.');
    }
    
    $teacher = $DB->get_record('user', array('id' => $userid));
    if (!$teacher) {
        throw new Exception('선생님 정보를 찾을 수 없습니다.');
    }
    
    // 권한 확인 - 현재 사용자가 해당 선생님 계정에 접근할 권한이 있는지 확인
    $current_user = $DB->get_record('user', array('id' => $USER->id));
    if (!$current_user) {
        throw new Exception('사용자 정보를 찾을 수 없습니다.');
    }
    
    // teacherid 필드가 있는 경우 검증
    if (isset($current_user->teacherid) && !empty($current_user->teacherid)) {
        if ($current_user->teacherid != $userid) {
            error_log("get_interaction_history.php - Access denied for user {$USER->id} trying to access teacher $userid");
            throw new Exception('접근 권한이 없습니다.');
        }
    }
    
    // 학생 정보 확인 (선택사항)
    $student = null;
    if ($studentid) {
        $student = $DB->get_record('user', array('id' => $studentid));
        if (!$student) {
            throw new Exception('학생 정보를 찾을 수 없습니다.');
        }
    }
    
    // ktm_teaching_interactions 테이블에서 데이터 가져오기
    $offset = $page * $perpage;
    
    // WHERE 조건 구성
    $where_conditions = array("teacherid = :userid");
    $params = array('userid' => $userid);
    
    // 학생 필터링
    if ($studentid) {
        $where_conditions[] = "userid = :studentid";
        $params['studentid'] = $studentid;
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
    
    $sql = "SELECT i.*, 'interaction' as source_type 
            FROM {ktm_teaching_interactions} i 
            WHERE $where_clause
            ORDER BY i.timecreated DESC";
    
    // 디버깅을 위한 로그
    error_log("get_interaction_history.php - SQL: $sql");
    error_log("get_interaction_history.php - Params: " . json_encode($params));
    
    $interactions = $DB->get_records_sql($sql, $params, $offset, $perpage);
    
    error_log("get_interaction_history.php - Found " . count($interactions) . " interactions");
    
    // 전체 상호작용 수 계산
    $sql_count = "SELECT COUNT(*) FROM {ktm_teaching_interactions} i WHERE $where_clause";
    $total_count = $DB->count_records_sql($sql_count, $params);
    error_log("get_interaction_history.php - Total interactions: $total_count");
    
    // 상호작용 데이터 포맷팅
    $formatted_interactions = array();
    foreach ($interactions as $interaction) {
        // 학생 정보 가져오기
        $student_info = $DB->get_record('user', array('id' => $interaction->userid));
        $student_name = $student_info ? fullname($student_info) : '알 수 없는 학생';
        
        // 상태에 따른 메시지 생성
        $status_text = '';
        $status_emoji = '';
        switch ($interaction->status) {
            case 'pending':
                $status_emoji = '⏳';
                $status_text = '대기중';
                break;
            case 'in_progress':
                $status_emoji = '🔄';
                $status_text = '진행중';
                break;
            case 'completed':
                $status_emoji = '✅';
                $status_text = '완료됨';
                break;
            default:
                $status_emoji = '❓';
                $status_text = '알 수 없음';
        }
        
        // 메시지 내용 생성
        $message_content = '';
        if ($interaction->problem_text) {
            $message_content = $interaction->problem_text;
        } else {
            $message_content = "문제 유형: " . ($interaction->problem_type ?? '일반 문제');
        }
        
        $formatted_interactions[] = array(
            'id' => $interaction->id,
            'student_id' => $interaction->userid,
            'student_name' => $student_name,
            'teacher_id' => $interaction->teacherid,
            'teacher_name' => fullname($teacher),
            'subject' => $status_emoji . ' ' . $student_name . ' - ' . $status_text,
            'fullmessage' => $message_content,
            'problem_text' => $interaction->problem_text,
            'problem_image' => $interaction->problem_image,
            'problem_type' => $interaction->problem_type,
            'solution_text' => $interaction->solution_text,
            'audio_url' => $interaction->audio_url,
            'status' => $interaction->status,
            'timecreated' => $interaction->timecreated,
            'timemodified' => $interaction->timemodified,
            'interaction_id' => $interaction->id,
            'message_type' => 'teaching_interaction'
        );
    }
    
    // 통계 데이터
    $today_start = strtotime('today');
    
    // 오늘의 통계 계산
    $today_total = $DB->count_records_select('ktm_teaching_interactions', 
        "$where_clause AND timecreated >= ?", 
        array_merge(array_values($params), array($today_start)));
    
    $today_completed = $DB->count_records_select('ktm_teaching_interactions', 
        "$where_clause AND status = 'completed' AND timecreated >= ?", 
        array_merge(array_values($params), array($today_start)));
    
    $today_pending = $DB->count_records_select('ktm_teaching_interactions', 
        "$where_clause AND status = 'pending' AND timecreated >= ?", 
        array_merge(array_values($params), array($today_start)));
    
    $stats = array(
        'total' => $total_count,
        'today_total' => $today_total,
        'today_completed' => $today_completed,
        'today_pending' => $today_pending
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
        ),
        'student' => $student ? array(
            'id' => $student->id,
            'name' => fullname($student),
            'email' => $student->email
        ) : null
    ));
    
} catch (Exception $e) {
    error_log("get_interaction_history.php - Error: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>