<?php
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();

// 사용자 LMS 데이터 가져오기
function getUserLMSData($userid) {
    global $DB;
    
    $lmsData = array(
        // 기본 정보
        'institute' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 88)), // 학교
        'birthdate' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 89)), // 출생년도
        
        // 연락처 정보
        'email_father' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 112)), // 이메일(부)
        'email_mother' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 113)), // 이메일(모)
        'phone_student' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 54)), // 학생 연락처
        'phone_father' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 85)), // 아버지 연락처
        'phone_mother' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 55)), // 어머니 연락처
        
        // 학습 환경
        'brotherhood' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 44)), // 형제관계
        'academy' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 46)), // 학원명
        'location' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 68)), // 지역
        'addcourse' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 83)), // 코스추천
        'roleinfo' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 22)), // 사용자 유형
        
        // 학습 능력/스타일
        'fluency' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 60)), // 사용법 능숙도
        'goalstability' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 80)), // 목표설정 안정도
        'effectivelearning' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 81)), // 논리분리
        'lmode' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 90)), // 신규/자율/지도/도제
        'evaluate' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 92)), // 완결형/도전형
        'curriculum' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 70)), // 쇠퇴형/표준형/성장형
        
        // 학습 활동
        'nboosters' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 86)), // 부스터 활동 횟수
        'inspecttime' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 72)), // 점검주기
        
        // 학습 시간
        'termhours' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 107)), // 학기중 주별 공부시간
        'vachours' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 108)), // 방학중 주별 공부시간
        
        // 대학/커리큘럼
        'univ' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 105)), // 대학
        'curtype' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 106)), // 커리큘럼 유형
        
        // PRESET 정보
        'preset_concept' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 93)), // 개념미션 PRESET
        'preset_advanced' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 94)), // 심화미션 PRESET
        'preset_school' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 95)), // 내신미션 PRESET
        'preset_csat' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 96)), // 수능미션 PRESET
        
        // 학습 수준
        'mathlevel' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 114)), // 학습수준
        'classtype' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 115)), // 보충과정
        'progresstype' => $DB->get_field('user_info_data', 'data', array('userid' => $userid, 'fieldid' => 116)), // 진도
    );
    
    // 학년 추출 (birthdate에서 계산)
    if ($lmsData['birthdate']) {
        $birthYear = intval($lmsData['birthdate']);
        
        // 2025년 기준 학년 매핑
        $gradeMap = array(
            2007 => array('grade' => 3, 'level' => 'high'),
            2008 => array('grade' => 2, 'level' => 'high'),
            2009 => array('grade' => 1, 'level' => 'high'),
            2010 => array('grade' => 3, 'level' => 'middle'),
            2011 => array('grade' => 2, 'level' => 'middle'),
            2012 => array('grade' => 1, 'level' => 'middle'),
            2013 => array('grade' => 6, 'level' => 'elementary'),
            2014 => array('grade' => 5, 'level' => 'elementary'),
            2015 => array('grade' => 4, 'level' => 'elementary'),
            2016 => array('grade' => 3, 'level' => 'elementary')
        );
        
        if (isset($gradeMap[$birthYear])) {
            $info = $gradeMap[$birthYear];
            $lmsData['grade_number'] = $info['grade'];
            
            if ($info['level'] == 'high') {
                $lmsData['grade'] = '고' . $info['grade'];
            } else if ($info['level'] == 'middle') {
                $lmsData['grade'] = '중' . $info['grade'];
            } else {
                $lmsData['grade'] = '초' . $info['grade'];
            }
            $lmsData['school_level'] = $info['level'];
        } else {
            $lmsData['grade'] = null;
            $lmsData['grade_number'] = null;
            $lmsData['school_level'] = null;
        }
        
        // 시험 종류 자동 판단
        $currentMonth = date('n');
        $currentDay = date('j');
        
        if (($currentMonth == 12 && $currentDay >= 11) || ($currentMonth >= 1 && $currentMonth <= 4) || ($currentMonth == 5 && $currentDay == 1)) {
            $lmsData['exam_type'] = '1mid'; // 1학기 중간고사
            $lmsData['semester'] = 1;
        } else if (($currentMonth == 5 && $currentDay >= 2) || ($currentMonth == 6)) {
            $lmsData['exam_type'] = '1final'; // 1학기 기말고사
            $lmsData['semester'] = 1;
        } else if ($currentMonth >= 7 && $currentMonth <= 9) {
            $lmsData['exam_type'] = '2mid'; // 2학기 중간고사
            $lmsData['semester'] = 2;
        } else if ($currentMonth >= 10 && $currentMonth <= 11 || ($currentMonth == 12 && $currentDay <= 10)) {
            $lmsData['exam_type'] = '2final'; // 2학기 기말고사
            $lmsData['semester'] = 2;
        }
    }
    
    return $lmsData;
}

// AJAX 요청 처리
if (isset($_GET['action']) && $_GET['action'] === 'get_lms_data') {
    header('Content-Type: application/json; charset=utf-8');
    
    $userid = optional_param('userid', $USER->id, PARAM_INT);
    $lmsData = getUserLMSData($userid);
    
    echo json_encode(array(
        'success' => true,
        'data' => $lmsData
    ), JSON_UNESCAPED_UNICODE);
    exit;
}
?>