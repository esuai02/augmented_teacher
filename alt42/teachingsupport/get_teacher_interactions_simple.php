<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    // 매개변수 처리 - 안전하게
    $teacherid = isset($_GET['teacherid']) ? (int)$_GET['teacherid'] : $USER->id;
    $studentid = isset($_GET['studentid']) ? (int)$_GET['studentid'] : 0;
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    // 기본 검증
    if ($teacherid <= 0) {
        $teacherid = $USER->id;
    }
    
    error_log("Simple API - TeacherId: $teacherid, StudentId: $studentid, Filter: $filter");
    
    // 기본 조건 - teacherid로 조회하고 완료된 항목만
    $conditions = array('teacherid' => $teacherid, 'status' => 'completed');
    
    // 학생 필터 추가
    if ($studentid > 0) {
        $conditions['userid'] = $studentid;
    }
    
    // 해설이 있는 항목만 (solution_text가 있는 것)
    $sql_conditions = "teacherid = ? AND status = 'completed' AND (solution_text IS NOT NULL AND solution_text != '')";
    $params = array($teacherid);
    
    if ($studentid > 0) {
        $sql_conditions .= " AND userid = ?";
        $params[] = $studentid;
    }
    
    // 데이터 조회 - 복잡한 조건이므로 SQL 사용
    $interactions = $DB->get_records_sql(
        "SELECT * FROM {ktm_teaching_interactions} WHERE $sql_conditions ORDER BY timecreated DESC LIMIT 50",
        $params
    );
    
    $formatted_interactions = array();
    $stats = array('total' => 0, 'completed' => 0, 'pending' => 0, 'in_progress' => 0);
    
    foreach ($interactions as $interaction) {
        // 학생 정보 조회
        $student = $DB->get_record('user', array('id' => $interaction->userid), 'firstname, lastname');
        $student_name = $student ? ($student->firstname . ' ' . $student->lastname) : '학생 정보 없음';
        
        $formatted_interactions[] = array(
            'id' => $interaction->id,
            'userid' => $interaction->userid,
            'student_name' => $student_name,
            'teacherid' => $interaction->teacherid,
            'problem_text' => $interaction->problem_text ?? '',
            'problem_image' => $interaction->problem_image ?? '',
            'problem_type' => $interaction->problem_type ?? '',
            'solution_text' => $interaction->solution_text ?? '',
            'audio_url' => $interaction->audio_url ?? '',
            'status' => $interaction->status ?? 'pending',
            'modification_prompt' => $interaction->modification_prompt ?? '',
            'timecreated' => $interaction->timecreated,
            'timemodified' => $interaction->timemodified ?? 0
        );
        
        // 통계 계산
        $stats['total']++;
        $status = $interaction->status ?? 'pending';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }
    
    echo json_encode(array(
        'success' => true,
        'interactions' => $formatted_interactions,
        'stats' => $stats,
        'debug' => array(
            'teacherid' => $teacherid,
            'studentid' => $studentid,
            'filter' => $filter,
            'conditions' => $conditions,
            'count' => count($formatted_interactions)
        )
    ));
    
} catch (Exception $e) {
    error_log("Simple API Error: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => array(
            'teacherid' => $teacherid ?? 0,
            'studentid' => $studentid ?? 0,
            'filter' => $filter ?? 'none'
        )
    ));
}
?>