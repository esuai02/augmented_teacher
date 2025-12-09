<?php
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

// 같은 학교, 학년, 시험 종류에 대한 확정된 시험 범위 가져오기
function getDefaultExamScope($school, $grade, $examType) {
    global $DB;
    
    // 시험 종류 매핑
    $exam_type_map = array(
        '1mid' => '1학기 중간고사',
        '1final' => '1학기 기말고사',
        '2mid' => '2학기 중간고사',
        '2final' => '2학기 기말고사'
    );
    
    $examTypeName = isset($exam_type_map[$examType]) ? $exam_type_map[$examType] : $examType;
    
    // 1. 먼저 같은 학교, 학년의 확정된 시험 정보 찾기
    $sql = "SELECT DISTINCT er.tip_text 
            FROM {alt42t_exams} e
            JOIN {alt42t_exam_resources} er ON e.exam_id = er.exam_id
            JOIN {alt42t_exam_dates} ed ON e.exam_id = ed.exam_id
            WHERE e.school_name = ? 
            AND e.grade = ? 
            AND e.exam_type = ?
            AND ed.status = '확정'
            AND er.tip_text LIKE '시험 범위:%'
            ORDER BY er.timecreated DESC
            LIMIT 1";
    
    $result = $DB->get_record_sql($sql, array($school, $grade, $examTypeName));
    
    if ($result && $result->tip_text) {
        // "시험 범위: " 부분 제거
        $scope = str_replace('시험 범위: ', '', $result->tip_text);
        return array(
            'success' => true,
            'scope' => $scope,
            'status' => 'confirmed',
            'message' => '동일 학교/학년의 확정된 시험 범위를 불러왔습니다.'
        );
    }
    
    // 2. 확정된 데이터가 없으면 가장 최근 데이터라도 가져오기
    $sql2 = "SELECT DISTINCT er.tip_text 
             FROM {alt42t_exams} e
             JOIN {alt42t_exam_resources} er ON e.exam_id = er.exam_id
             WHERE e.school_name = ? 
             AND e.grade = ? 
             AND e.exam_type = ?
             AND er.tip_text LIKE '시험 범위:%'
             ORDER BY er.timecreated DESC
             LIMIT 1";
    
    $result2 = $DB->get_record_sql($sql2, array($school, $grade, $examTypeName));
    
    if ($result2 && $result2->tip_text) {
        $scope = str_replace('시험 범위: ', '', $result2->tip_text);
        return array(
            'success' => true,
            'scope' => $scope,
            'status' => 'expected',
            'message' => '동일 학교/학년의 예상 시험 범위를 불러왔습니다.'
        );
    }
    
    return array(
        'success' => false,
        'scope' => '',
        'status' => 'expected',
        'message' => '이전 시험 범위 정보가 없습니다.'
    );
}

// AJAX 요청 처리
if (isset($_GET['action']) && $_GET['action'] === 'get_default_scope') {
    $school = required_param('school', PARAM_TEXT);
    $grade = required_param('grade', PARAM_INT);
    $examType = required_param('examType', PARAM_TEXT);
    
    $result = getDefaultExamScope($school, $grade, $examType);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
?>