<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// JSON 입력 데이터 받기
$input_raw = file_get_contents("php://input");

// 디버깅용 로그
error_log('save_exam_data_alt42t.php - Request Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('save_exam_data_alt42t.php - Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log('save_exam_data_alt42t.php - Raw input length: ' . strlen($input_raw));
error_log('save_exam_data_alt42t.php - Raw input (first 500 chars): ' . substr($input_raw, 0, 500));

// JSON 디코딩
$input = json_decode($input_raw, true);

// JSON 디코딩 오류 확인
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'JSON 파싱 오류: ' . json_last_error_msg(),
        'raw_input' => substr($input_raw, 0, 200) // 처음 200자만
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

error_log('save_exam_data_alt42t.php - Current user ID: ' . $USER->id);
error_log('save_exam_data_alt42t.php - Decoded input: ' . print_r($input, true));

// 입력 데이터 검증
if (!$input || !is_array($input)) {
    // POST 데이터로도 시도
    if (!empty($_POST)) {
        $input = $_POST;
        error_log('save_exam_data_alt42t.php - Using POST data: ' . print_r($_POST, true));
    } else {
        echo json_encode([
            'success' => false,
            'message' => '입력 데이터가 없습니다',
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'raw_length' => strlen($input_raw)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// userid 확인
$userid = isset($input['userid']) ? intval($input['userid']) : $USER->id;
$section = isset($input['section']) ? intval($input['section']) : 0;

error_log('save_exam_data_alt42t.php - Using userid: ' . $userid . ', section: ' . $section);

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    $now = time();
    
    // 디버깅 - 받은 데이터 확인
    error_log('=== save_exam_data_alt42t.php DEBUG START ===');
    error_log('Section: ' . $section);
    error_log('Input data: ' . print_r($input, true));
    
    // mdl_user_info_data에 데이터 저장하는 함수
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
    
    // Section별 처리
    if ($section === 0) {
        // 기본 정보 저장 (학교, 학년, 시험종류)
        
        // 1. mdl_alt42t_users 테이블에 사용자 정보 저장/업데이트
        $existing_user = $DB->get_record('alt42t_users', array('userid' => $userid));
        error_log('Existing user: ' . print_r($existing_user, true));
        
        $user_data = new stdClass();
        $user_data->userid = $userid;
        $user_data->name = $USER->firstname . ' ' . $USER->lastname;
        $user_data->school_name = trim($input['school']);
        
        // grade 처리 - 문자열에서 숫자 추출 또는 직접 숫자 사용
        $gradeValue = $input['grade'];
        if (is_string($gradeValue) && preg_match('/(\d+)/', $gradeValue, $matches)) {
            $user_data->grade = intval($matches[1]);
        } else {
            $user_data->grade = intval($gradeValue);
        }
        
        $user_data->timecreated = $now;
        $user_data->timemodified = $now;
        
        if ($existing_user) {
            // 직접 SQL UPDATE 사용
            $sql = "UPDATE {alt42t_users} 
                    SET name = :name,
                        school_name = :school_name,
                        grade = :grade,
                        timemodified = :timemodified
                    WHERE userid = :userid";
            
            $params = array(
                'name' => $USER->firstname . ' ' . $USER->lastname,
                'school_name' => trim($input['school']),
                'grade' => $user_data->grade,
                'timemodified' => $now,
                'userid' => $userid
            );
            
            try {
                $DB->execute($sql, $params);
                $user_id = $existing_user->id;
                error_log('Updated alt42t_users for user_id: ' . $user_id);
            } catch (Exception $e) {
                error_log('Error updating alt42t_users: ' . $e->getMessage());
                throw new Exception('데이터베이스 쓰기 오류: ' . $e->getMessage());
            }
        } else {
            $user_data->created_at = date('Y-m-d H:i:s');
            $user_id = $DB->insert_record('alt42t_users', $user_data);
            error_log('Inserted new alt42t_users with user_id: ' . $user_id);
        }
        
        // 2. mdl_alt42t_exams 테이블에 시험 정보 저장/업데이트
        $exam_type_map = [
            '1mid' => '1학기 중간고사',
            '1final' => '1학기 기말고사',
            '2mid' => '2학기 중간고사',
            '2final' => '2학기 기말고사'
        ];
        
        $exam_type_str = $exam_type_map[$input['examType']] ?? $input['examType'];
        
        // grade 처리 - 문자열에서 숫자 추출 또는 직접 숫자 사용
        $gradeValue = $input['grade'];
        if (is_string($gradeValue) && preg_match('/(\d+)/', $gradeValue, $matches)) {
            $gradeNum = intval($matches[1]);
        } else {
            $gradeNum = intval($gradeValue);
        }
        
        // 먼저 현재 사용자의 기존 시험 정보 확인
        $user_exam = null;
        
        // 1. 먼저 간단한 쿼리로 시도
        $user_exam = $DB->get_record('alt42t_exams', array(
            'school_name' => trim($input['school']),
            'grade' => $gradeNum,
            'userid' => $userid
        ));
        
        // 2. 없으면 user_id를 통해 찾기
        if (!$user_exam) {
            try {
                $user_exam = $DB->get_record_sql("
                    SELECT e.* 
                    FROM {alt42t_exams} e
                    JOIN {alt42t_exam_dates} ed ON e.exam_id = ed.exam_id
                    WHERE ed.user_id = ? 
                    ORDER BY e.timemodified DESC
                    LIMIT 1", array($user_id));
            } catch (Exception $e) {
                error_log('Error fetching existing exam with JOIN: ' . $e->getMessage());
            }
        }
        
        error_log('User exam found: ' . ($user_exam ? 'Yes' : 'No'));
        if ($user_exam) {
            error_log('Exam record: ' . print_r($user_exam, true));
        }
        
        if ($user_exam) {
            // 기존 시험 정보가 있으면 업데이트
            try {
                // exam_id 또는 id 중 존재하는 것 사용
                $id_field = isset($user_exam->exam_id) ? 'exam_id' : 'id';
                $id_value = isset($user_exam->exam_id) ? $user_exam->exam_id : $user_exam->id;
                
                $sql = "UPDATE {alt42t_exams} 
                        SET school_name = :school_name,
                            grade = :grade,
                            exam_type = :exam_type,
                            timemodified = :timemodified
                        WHERE $id_field = :id_value";
                
                $params = array(
                    'school_name' => trim($input['school']),
                    'grade' => $gradeNum,
                    'exam_type' => $exam_type_str,
                    'timemodified' => $now,
                    'id_value' => $id_value
                );
                
                error_log("UPDATE query: $sql");
                error_log("UPDATE params: " . print_r($params, true));
                
                $DB->execute($sql, $params);
                $exam_id = $id_value;
                error_log('Updated existing alt42t_exams with ' . $id_field . ': ' . $exam_id);
            } catch (Exception $e) {
                error_log('Error updating alt42t_exams: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw new Exception('데이터베이스 쓰기 오류');
            }
        } else {
            // 새로운 시험 정보 생성
            $exam_data = new stdClass();
            $exam_data->school_name = trim($input['school']);
            $exam_data->grade = $gradeNum;
            $exam_data->exam_type = $exam_type_str;
            $exam_data->userid = $userid;
            $exam_data->timecreated = $now;
            $exam_data->timemodified = $now;
            
            try {
                $exam_id = $DB->insert_record('alt42t_exams', $exam_data);
                error_log('Inserted new alt42t_exams with id: ' . $exam_id);
            } catch (Exception $e) {
                error_log('Error inserting alt42t_exams: ' . $e->getMessage());
                throw new Exception('시험 정보 생성 오류');
            }
        }
        
        // 세션에 user_id와 exam_id 저장 (다음 섹션에서 사용)
        $_SESSION['alt42t_user_id'] = $user_id;
        $_SESSION['alt42t_exam_id'] = $exam_id;
        
        // 3. mdl_user_info_data 테이블에도 정보 저장
        // 학교 정보 저장 (fieldid: 88)
        if (!empty(trim($input['school']))) {
            saveUserInfoData($userid, 88, trim($input['school']));
            error_log('Saved school to mdl_user_info_data: ' . trim($input['school']));
        }
        
        // 출생년도 계산 및 저장 (fieldid: 89)
        if (!empty($input['grade'])) {
            // 이미 위에서 계산한 gradeNum 사용
            $grade = $gradeNum;
            
            // 학년에서 출생년도 역계산 (2025년 기준)
            // 학교 이름 또는 학년 문자열에서 학교급 판단
            $school_name = trim($input['school']);
            $grade_string = $input['grade'];
            
            $is_elementary = (strpos($school_name, '초등') !== false) || (strpos($grade_string, '초등학교') !== false);
            $is_middle = (strpos($school_name, '중학') !== false) || (strpos($grade_string, '중학교') !== false);
            $is_high = (strpos($school_name, '고등') !== false || strpos($school_name, '고교') !== false) || (strpos($grade_string, '고등학교') !== false);
            
            $birthYearMap = array(
                // 고등학교
                'high' => array(
                    3 => 2007,
                    2 => 2008,
                    1 => 2009
                ),
                // 중학교
                'middle' => array(
                    3 => 2010,
                    2 => 2011,
                    1 => 2012
                ),
                // 초등학교
                'elementary' => array(
                    6 => 2013,
                    5 => 2014,
                    4 => 2015,
                    3 => 2016
                )
            );
            
            $birthYear = 0;
            if ($is_elementary && isset($birthYearMap['elementary'][$grade])) {
                $birthYear = $birthYearMap['elementary'][$grade];
            } else if ($is_middle && isset($birthYearMap['middle'][$grade])) {
                $birthYear = $birthYearMap['middle'][$grade];
            } else if ($is_high && isset($birthYearMap['high'][$grade])) {
                $birthYear = $birthYearMap['high'][$grade];
            } else {
                // 학교급을 판단할 수 없는 경우, 시험 종류로 추정
                if ($grade >= 1 && $grade <= 3) {
                    // 기본적으로 고등학교로 가정
                    $birthYear = isset($birthYearMap['high'][$grade]) ? $birthYearMap['high'][$grade] : 0;
                } else if ($grade >= 4 && $grade <= 6) {
                    // 초등학교로 가정
                    $birthYear = isset($birthYearMap['elementary'][$grade]) ? $birthYearMap['elementary'][$grade] : 0;
                }
            }
            
            // 기존 출생년도가 없는 경우에만 저장
            $existing_birthdate = $DB->get_field('user_info_data', 'data', array(
                'userid' => $userid,
                'fieldid' => 89
            ));
            
            if (!$existing_birthdate && $birthYear > 0) {
                saveUserInfoData($userid, 89, $birthYear);
                error_log('Saved birth year to mdl_user_info_data: ' . $birthYear);
            }
        }
        
        $message = "기본 정보가 저장되었습니다.";
        
    } else if ($section === 1) {
        // 시험 일정 저장
        
        // 저장된 user_id와 exam_id 가져오기
        $user_id = $_SESSION['alt42t_user_id'] ?? null;
        $exam_id = $_SESSION['alt42t_exam_id'] ?? null;
        
        if (!$user_id || !$exam_id) {
            // 세션에 없으면 DB에서 다시 조회
            $user_record = $DB->get_record('alt42t_users', array('userid' => $userid));
            if ($user_record) {
                $user_id = $user_record->id;
                
                // exam_id도 조회
                $exam_record = $DB->get_record_sql("
                    SELECT e.* FROM {alt42t_exams} e
                    WHERE e.school_name = ? AND e.grade = ?
                    ORDER BY e.timecreated DESC
                    LIMIT 1
                ", array($user_record->school_name, $user_record->grade));
                
                if ($exam_record) {
                    $exam_id = $exam_record->exam_id;
                }
            }
        }
        
        if (!$user_id || !$exam_id) {
            throw new Exception('기본 정보를 먼저 저장해주세요.');
        }
        
        // mdl_alt42t_exam_dates에 저장/업데이트
        $existing_date = $DB->get_record('alt42t_exam_dates', array(
            'exam_id' => $exam_id,
            'user_id' => $user_id
        ));
        
        if ($existing_date) {
            // 직접 SQL UPDATE 사용
            $sql = "UPDATE {alt42t_exam_dates} 
                    SET start_date = :start_date,
                        end_date = :end_date,
                        math_date = :math_date,
                        status = :status,
                        userid = :userid,
                        timemodified = :timemodified
                    WHERE exam_id = :exam_id AND user_id = :user_id";
            
            $params = array(
                'start_date' => $input['startDate'],
                'end_date' => $input['endDate'] ?: null,
                'math_date' => $input['mathExamDate'] ?: null,
                'status' => ($input['status'] === 'confirmed') ? '확정' : '예상',
                'userid' => $userid,
                'timemodified' => $now,
                'exam_id' => $exam_id,
                'user_id' => $user_id
            );
            
            $DB->execute($sql, $params);
            error_log('Updated alt42t_exam_dates using direct SQL');
        } else {
            $date_data = new stdClass();
            $date_data->exam_id = $exam_id;
            $date_data->user_id = $user_id;
            $date_data->start_date = $input['startDate'];
            $date_data->end_date = $input['endDate'] ?: null;
            $date_data->math_date = $input['mathExamDate'] ?: null;
            $date_data->status = ($input['status'] === 'confirmed') ? '확정' : '예상';
            $date_data->userid = $userid;
            $date_data->created_at = date('Y-m-d H:i:s');
            $date_data->timecreated = $now;
            $date_data->timemodified = $now;
            
            $DB->insert_record('alt42t_exam_dates', $date_data);
            error_log('Inserted new alt42t_exam_dates');
        }
        
        // exam_scope가 있으면 alt42t_exam_resources에 저장
        if (!empty($input['examScope'])) {
            $existing_resource = $DB->get_record('alt42t_exam_resources', array(
                'exam_id' => $exam_id,
                'user_id' => $user_id
            ));
            
            if ($existing_resource) {
                // 직접 SQL UPDATE 사용
                $sql = "UPDATE {alt42t_exam_resources} 
                        SET tip_text = :tip_text,
                            userid = :userid,
                            timemodified = :timemodified
                        WHERE exam_id = :exam_id AND user_id = :user_id";
                
                $params = array(
                    'tip_text' => '시험 범위: ' . $input['examScope'],
                    'userid' => $userid,
                    'timemodified' => $now,
                    'exam_id' => $exam_id,
                    'user_id' => $user_id
                );
                
                $DB->execute($sql, $params);
            } else {
                $resource_data = new stdClass();
                $resource_data->exam_id = $exam_id;
                $resource_data->user_id = $user_id;
                $resource_data->tip_text = '시험 범위: ' . $input['examScope'];
                $resource_data->userid = $userid;
                $resource_data->created_at = date('Y-m-d H:i:s');
                $resource_data->timecreated = $now;
                $resource_data->timemodified = $now;
                
                $DB->insert_record('alt42t_exam_resources', $resource_data);
            }
        }
        
        $message = "시험 일정이 저장되었습니다.";
        
    } else if ($section === 3) {
        // 학습 상태 저장
        
        // 저장된 user_id와 exam_id 가져오기
        $user_id = $_SESSION['alt42t_user_id'] ?? null;
        $exam_id = $_SESSION['alt42t_exam_id'] ?? null;
        
        if (!$user_id || !$exam_id) {
            // 세션에 없으면 DB에서 다시 조회
            $user_record = $DB->get_record('alt42t_users', array('userid' => $userid));
            if ($user_record) {
                $user_id = $user_record->id;
                
                // exam_id도 조회
                $exam_record = $DB->get_record_sql("
                    SELECT e.* FROM {alt42t_exams} e
                    WHERE e.school_name = ? AND e.grade = ?
                    ORDER BY e.timecreated DESC
                    LIMIT 1
                ", array($user_record->school_name, $user_record->grade));
                
                if ($exam_record) {
                    $exam_id = $exam_record->exam_id;
                }
            }
        }
        
        if (!$user_id || !$exam_id) {
            throw new Exception('기본 정보를 먼저 저장해주세요.');
        }
        
        // mdl_alt42t_study_status에 저장/업데이트
        $existing_status = $DB->get_record('alt42t_study_status', array(
            'user_id' => $user_id,
            'exam_id' => $exam_id
        ));
        
        // studyLevel 또는 studyStatus 처리
        $study_value = isset($input['studyLevel']) ? $input['studyLevel'] : (isset($input['studyStatus']) ? $input['studyStatus'] : '');
        
        if ($existing_status) {
            // 직접 SQL UPDATE 사용
            $sql = "UPDATE {alt42t_study_status} 
                    SET status = :status,
                        study_level = :study_level,
                        userid = :userid,
                        timemodified = :timemodified
                    WHERE user_id = :user_id AND exam_id = :exam_id";
            
            $params = array(
                'status' => $study_value,
                'study_level' => $study_value,
                'userid' => $userid,
                'timemodified' => $now,
                'user_id' => $user_id,
                'exam_id' => $exam_id
            );
            
            $DB->execute($sql, $params);
            error_log('Updated alt42t_study_status with study_level: ' . $study_value);
        } else {
            $status_data = new stdClass();
            $status_data->user_id = $user_id;
            $status_data->exam_id = $exam_id;
            $status_data->status = $study_value;
            $status_data->study_level = $study_value;
            $status_data->userid = $userid;
            $status_data->created_at = date('Y-m-d H:i:s');
            $status_data->timecreated = $now;
            $status_data->timemodified = $now;
            
            $DB->insert_record('alt42t_study_status', $status_data);
            error_log('Inserted new alt42t_study_status with study_level: ' . $study_value);
        }
        
        $message = "학습 상태가 저장되었습니다.";
        
    } else {
        $message = "알 수 없는 섹션입니다.";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'user_id' => $user_id ?? null,
        'exam_id' => $exam_id ?? null
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('save_exam_data_alt42t.php 오류: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => '저장 중 오류 발생: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>