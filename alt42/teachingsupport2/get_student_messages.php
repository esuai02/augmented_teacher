<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$studentid = $_GET['studentid'] ?? $USER->id;
$page = $_GET['page'] ?? 0;
$perpage = $_GET['perpage'] ?? 10;

try {
    // 디버깅 로그 추가
    error_log("get_student_messages.php - 받은 메시지 조회 시작: StudentId: $studentid, UserId: {$USER->id}, Page: $page, PerPage: $perpage");
    
    // 권한 확인
    $context = context_system::instance();
    if ($studentid != $USER->id && !has_capability('moodle/site:config', $context)) {
        error_log("get_student_messages.php - Access denied for user {$USER->id} trying to access student $studentid");
        throw new Exception('접근 권한이 없습니다.');
    }

    // 학생 정보 확인
    $student = $DB->get_record('user', array('id' => $studentid));
    if (!$student) {
        throw new Exception('학생 정보를 찾을 수 없습니다.');
    }

    // ktm_teaching_interactions 테이블에서 직접 가져오기
    $offset = $page * $perpage;
    
    // 받은 메시지는 현재 시스템에서는 빈 배열로 반환
    // 실제로는 별도의 메시지 시스템이나 교사가 직접 보내는 메시지가 있어야 함
    // 현재는 풀이요청-응답 시스템이므로 받은 메시지는 없음
    $sql = "SELECT * FROM {ktm_teaching_interactions} 
            WHERE userid = :studentid 
            AND status = 'completed' 
            AND solution_text IS NOT NULL
            AND teacherid IS NOT NULL
            AND teacherid != userid
            ORDER BY timecreated DESC";
    $params = array('studentid' => $studentid);
    
    // 디버깅을 위한 로그
    error_log("get_student_messages.php - 받은 메시지 조회: 학생이 요청하고 교사가 응답 완료한 상호작용");
    
    $messages = $DB->get_records_sql($sql, $params, $offset, $perpage);
    
    error_log("get_student_messages.php - Found " . count($messages) . " interactions for student $studentid");
    
    // 전체 메시지 수 계산
    $sql_count = "SELECT COUNT(*) FROM {ktm_teaching_interactions} 
                  WHERE userid = :studentid 
                  AND status = 'completed' 
                  AND solution_text IS NOT NULL";
    $total_count = $DB->count_records_sql($sql_count, $params);
    error_log("get_student_messages.php - Total interactions: $total_count");
    
    // 읽지 않은 메시지 수 계산
    $unread_count = 0;
    if ($DB->get_manager()->table_exists('ktm_interaction_read_status')) {
        $sql_unread = "SELECT COUNT(DISTINCT ti.id) 
                       FROM {ktm_teaching_interactions} ti
                       LEFT JOIN {ktm_interaction_read_status} rs 
                            ON ti.id = rs.interaction_id AND rs.student_id = :studentid2
                       WHERE ti.userid = :studentid 
                       AND ti.status = 'completed' 
                       AND ti.solution_text IS NOT NULL
                       AND (rs.is_read IS NULL OR rs.is_read = 0)";
        $unread_count = $DB->count_records_sql($sql_unread, array('studentid' => $studentid, 'studentid2' => $studentid));
    } else {
        $unread_count = $total_count;
    }
    
    // 메시지 데이터 포맷팅
    $formatted_messages = array();
    foreach ($messages as $message) {
        // 선생님 정보 가져오기
        $teacher = $DB->get_record('user', array('id' => $message->teacherid));
        $teacher_name = $teacher ? fullname($teacher) : '알 수 없는 선생님';
        $teacher_email = $teacher ? $teacher->email : '';
        
        // 읽음 상태 확인
        $is_read = 0;
        $timeread = null;
        if ($DB->get_manager()->table_exists('ktm_interaction_read_status')) {
            $read_status = $DB->get_record('ktm_interaction_read_status', 
                array('interaction_id' => $message->id, 'student_id' => $studentid));
            if ($read_status && $read_status->is_read) {
                $is_read = 1;
                $timeread = $read_status->timeread;
            }
        }
        
        // 받은 메시지로 분류: 교사가 학생의 요청에 대해 해설을 완료한 경우
        $auto_message = "📩 선생님께서 회신하셨습니다!\n\n";
        $auto_message .= "📚 문제 유형: " . ($message->problem_type ?? '일반 문제') . "\n";
        $auto_message .= "🎯 해설 완료: " . date('Y-m-d H:i:s', $message->timemodified ?? $message->timecreated) . "\n";
        $auto_message .= "👨‍🏫 담당 선생님: " . $teacher_name . "\n\n";
        $auto_message .= "아래 '해설 보기' 버튼을 클릭하여 상세한 설명을 확인하세요!";
        
        $formatted_messages[] = array(
            'id' => $message->id,
            'teacher_name' => $teacher_name,
            'teacher_email' => $teacher_email,
            'subject' => '📩 ' . $teacher_name . ' 선생님 회신',
            'fullmessage' => $auto_message,
            'solution_text' => $message->solution_text,
            'audio_url' => $message->audio_url,
            'explanation_url' => '',
            'timecreated' => $message->timemodified ?? $message->timecreated,
            'timeread' => $timeread,
            'is_read' => $is_read,
            'interaction_id' => $message->id,
            'type' => $message->type ?? '',  // askhint 등 요청 타입
            'message_type' => 'teacher_response'  // 받은 메시지 타입 명시
        );
    }
    
    // 통계 데이터
    $stats = array(
        'total' => $total_count,
        'unread' => $unread_count,
        'read' => $total_count - $unread_count
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
        'messages' => $formatted_messages,
        'stats' => $stats,
        'pagination' => $pagination
    ));
    
} catch (Exception $e) {
    error_log("get_student_messages.php - Error: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>