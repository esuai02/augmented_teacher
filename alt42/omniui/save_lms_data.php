<?php
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

// JSON 입력 데이터 받기
$input_raw = file_get_contents("php://input");
$input = json_decode($input_raw, true);

// userid 확인
$userid = isset($input['userid']) ? intval($input['userid']) : $USER->id;

// 학교와 출생년도 정보를 mdl_user_info_data 테이블에 저장하는 함수
function saveUserInfoData($userid, $fieldid, $data) {
    global $DB;
    
    // 기존 데이터 확인
    $existing = $DB->get_record('user_info_data', array(
        'userid' => $userid,
        'fieldid' => $fieldid
    ));
    
    if ($existing) {
        // 업데이트
        $existing->data = $data;
        $DB->update_record('user_info_data', $existing);
    } else {
        // 신규 삽입
        $record = new stdClass();
        $record->userid = $userid;
        $record->fieldid = $fieldid;
        $record->data = $data;
        $record->dataformat = 0;
        $DB->insert_record('user_info_data', $record);
    }
}

try {
    $updated_fields = array();
    
    // 학교 정보 저장 (fieldid: 88)
    if (isset($input['school']) && !empty($input['school'])) {
        saveUserInfoData($userid, 88, trim($input['school']));
        $updated_fields[] = '학교';
    }
    
    // 출생년도 계산 및 저장 (fieldid: 89)
    if (isset($input['grade']) && !empty($input['grade'])) {
        $grade = intval($input['grade']);
        $currentYear = date('Y');
        
        // 학년에서 출생년도 역계산
        // 고3 = 2007년생, 고2 = 2008년생, 고1 = 2009년생
        // 중3 = 2010년생, 중2 = 2011년생, 중1 = 2012년생
        $birthYear = 0;
        
        // 학년 문자열 처리
        if (strpos($input['grade'], '고') !== false) {
            $gradeNum = intval(str_replace(['고', '학년'], '', $input['grade']));
            $birthYear = $currentYear - 16 - $gradeNum;
        } else if (strpos($input['grade'], '중') !== false) {
            $gradeNum = intval(str_replace(['중', '학년'], '', $input['grade']));
            $birthYear = $currentYear - 13 - $gradeNum;
        } else {
            // 숫자만 있는 경우 고등학생으로 가정
            $birthYear = $currentYear - 16 - $grade;
        }
        
        // 기존 출생년도가 없는 경우에만 저장
        $existing_birthdate = $DB->get_field('user_info_data', 'data', array(
            'userid' => $userid,
            'fieldid' => 89
        ));
        
        if (!$existing_birthdate && $birthYear > 0) {
            saveUserInfoData($userid, 89, $birthYear);
            $updated_fields[] = '출생년도';
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'LMS 데이터가 성공적으로 저장되었습니다.',
        'updated_fields' => $updated_fields
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('save_lms_data.php 오류: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => '저장 중 오류 발생: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>