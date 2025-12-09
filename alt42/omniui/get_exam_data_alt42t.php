<?php
// 기존 alt42t_ 테이블들에서 데이터를 가져오는 함수

function getExamDataFromAlt42t($userid) {
    global $DB;
    
    error_log('=== getExamDataFromAlt42t 시작 ===');
    error_log('userid: ' . $userid);
    
    // 1. alt42t_users에서 사용자 정보 가져오기
    $user_record = $DB->get_record('alt42t_users', array('userid' => $userid));
    error_log('user_record: ' . print_r($user_record, true));
    
    if (!$user_record) {
        // 사용자 정보가 없을 경우 빈 정보 반환
        return (object)[
            'school' => '',
            'grade' => '',
            'exam_type' => '',
            'exam_start_date' => '',
            'exam_end_date' => '',
            'math_exam_date' => '',
            'exam_scope' => '',
            'exam_status' => 'expected',
            'study_status' => ''
        ];
    }
    
    // 2. alt42t_exams에서 사용자별 최신 시험 정보 가져오기
    // 먼저 사용자가 직접 입력한 시험 정보가 있는지 확인
    $exam_record = $DB->get_record_sql("
        SELECT e.* FROM {alt42t_exams} e
        WHERE e.school_name = ? AND e.grade = ? 
        AND EXISTS (
            SELECT 1 FROM {alt42t_exam_dates} ed 
            WHERE ed.exam_id = e.exam_id 
            AND ed.user_id = ?
        )
        ORDER BY e.timecreated DESC 
        LIMIT 1
    ", array($user_record->school_name, $user_record->grade, $user_record->id));
    
    // 사용자별 시험 정보가 없으면 학교/학년 공통 시험 정보 조회
    if (!$exam_record) {
        $exam_record = $DB->get_record_sql("
            SELECT * FROM {alt42t_exams} 
            WHERE school_name = ? AND grade = ? 
            ORDER BY timecreated DESC 
            LIMIT 1
        ", array($user_record->school_name, $user_record->grade));
    }
    
    if (!$exam_record) {
        // 시험 정보가 없어도 기본 사용자 정보는 반환
        return (object)[
            'school' => $user_record->school_name,
            'grade' => $user_record->grade,
            'exam_type' => '',
            'exam_start_date' => '',
            'exam_end_date' => '',
            'math_exam_date' => '',
            'exam_scope' => '',
            'exam_status' => 'expected',
            'study_status' => ''
        ];
    }
    
    // 3. alt42t_exam_dates에서 날짜 정보 가져오기
    error_log('exam_id: ' . $exam_record->exam_id . ', user_id: ' . $user_record->id);
    $date_record = $DB->get_record('alt42t_exam_dates', array(
        'exam_id' => $exam_record->exam_id,
        'user_id' => $user_record->id
    ));
    error_log('date_record: ' . print_r($date_record, true));
    
    // 4. alt42t_study_status에서 학습 상태 가져오기
    $status_record = $DB->get_record('alt42t_study_status', array(
        'user_id' => $user_record->id,
        'exam_id' => $exam_record->exam_id
    ));
    
    // 5. alt42t_exam_resources에서 시험 범위 가져오기 (tip_text에서 추출)
    $resource_record = $DB->get_record('alt42t_exam_resources', array(
        'exam_id' => $exam_record->exam_id,
        'user_id' => $user_record->id
    ));
    
    $exam_scope = '';
    if ($resource_record && $resource_record->tip_text) {
        // "시험 범위: " 제거
        $exam_scope = str_replace('시험 범위: ', '', $resource_record->tip_text);
    }
    
    // exam_type 변환 (한글 -> 영문 코드)
    $exam_type_map = [
        '1학기 중간고사' => '1mid',
        '1학기 기말고사' => '1final',
        '2학기 중간고사' => '2mid',
        '2학기 기말고사' => '2final'
    ];
    
    $exam_type_code = $exam_type_map[$exam_record->exam_type] ?? '';
    
    // 결과 반환
    $result = (object)[
        'school' => $user_record->school_name,
        'grade' => $user_record->grade,
        'exam_type' => $exam_type_code,
        'exam_start_date' => $date_record ? $date_record->start_date : '',
        'exam_end_date' => $date_record ? $date_record->end_date : '',
        'math_exam_date' => $date_record ? $date_record->math_date : '',
        'exam_scope' => $exam_scope,
        'exam_status' => $date_record ? ($date_record->status === '확정' ? 'confirmed' : 'expected') : 'expected',
        'study_status' => $status_record ? $status_record->status : ''
    ];
    
    error_log('=== getExamDataFromAlt42t 결과 ===');
    error_log('result: ' . print_r($result, true));
    
    return $result;
}
?>