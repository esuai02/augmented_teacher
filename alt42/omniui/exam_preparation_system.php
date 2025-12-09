<?php
// Moodle 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// URL 파라미터에서 userid 가져오기
$userid = optional_param('userid', 0, PARAM_INT);
if ($userid == 0) {
    $userid = $USER->id;
}

// 사용자 정보 가져오기
$user = $DB->get_record('user', array('id' => $userid));
if (!$user) {
    print_error('User not found');
}

// 학년 계산 함수
function calculateGrade($birthYear) {
    // 출생년도로 바로 학년 매핑
    $gradeMap = array(
        2007 => '고등학교 3학년',
        2008 => '고등학교 2학년',
        2009 => '고등학교 1학년',
        2010 => '중학교 3학년',
        2011 => '중학교 2학년',
        2012 => '중학교 1학년'
    );
    
    // 디버깅용
    error_log("calculateGrade - birthYear: $birthYear");
    
    $result = isset($gradeMap[$birthYear]) ? $gradeMap[$birthYear] : '';
    error_log("calculateGrade - result: $result");
    
    return $result;
}

// 현재 날짜를 기준으로 학기 계산
function calculateSemester() {
    $currentMonth = intval(date('n'));
    return ($currentMonth >= 1 && $currentMonth <= 8) ? '1학기' : '2학기';
}

// 현재 날짜를 기준으로 시험 종류와 대표 날짜 계산
function calculateExamTypeAndDate() {
    $currentDate = new DateTime();
    $month = $currentDate->format('n');
    $day = $currentDate->format('j');
    $year = $currentDate->format('Y');
    
    // 더 정확한 날짜 기준으로 시험 종류 결정
    if ($month == 1 || $month == 2) {
        // 1-2월: 이전 년도 2학기 기말고사 준비
        return array('type' => '2학기 기말고사', 'date' => ($year - 1) . '-12-10', 'examTypeCode' => '2final');
    } else if ($month == 3 || $month == 4 || ($month == 5 && $day <= 10)) {
        // 3-5월 초: 1학기 중간고사 준비
        return array('type' => '1학기 중간고사', 'date' => $year . '-05-01', 'examTypeCode' => '1mid');
    } else if (($month == 5 && $day > 10) || $month == 6 || ($month == 7 && $day <= 10)) {
        // 5월 중순-7월 초: 1학기 기말고사 준비
        return array('type' => '1학기 기말고사', 'date' => $year . '-07-01', 'examTypeCode' => '1final');
    } else if (($month == 7 && $day > 10) || $month == 8 || $month == 9 || ($month == 10 && $day <= 10)) {
        // 7월 중순-10월 초: 2학기 중간고사 준비
        return array('type' => '2학기 중간고사', 'date' => $year . '-10-01', 'examTypeCode' => '2mid');
    } else {
        // 10월 중순-12월: 2학기 기말고사 준비
        return array('type' => '2학기 기말고사', 'date' => $year . '-12-10', 'examTypeCode' => '2final');
    }
}

// mathking DB에서 사용자 정보 가져오기
// save_lms_data.php와 동일하게 fieldid 88(학교), 89(출생년도) 사용
$userinfo = $DB->get_record_sql("
    SELECT u.id, u.firstname, u.lastname, 
           (SELECT data FROM {user_info_data} WHERE userid = u.id AND fieldid = 89 LIMIT 1) as birthdate,
           (SELECT data FROM {user_info_data} WHERE userid = u.id AND fieldid = 88 LIMIT 1) as institute
    FROM {user} u 
    WHERE u.id = ?", array($userid));

// 기본 정보 설정
$firstname = $userinfo->firstname ?? '';
$lastname = $userinfo->lastname ?? '';
$fullname = $firstname . $lastname;
$school = $userinfo->institute ?? '';

// 출생년도 파싱 (다양한 형식 처리)
$birthYear = 0;
$birthData = $userinfo->birthdate ?? '';

// 디버깅용 - 나중에 제거
error_log("Birth data from DB: " . $birthData);

if ($birthData) {
    // 모든 공백 제거
    $cleanData = trim($birthData);
    
    // YYYY-MM-DD 형식
    if (preg_match('/(\d{4})-\d{2}-\d{2}/', $cleanData, $matches)) {
        $birthYear = intval($matches[1]);
        error_log("Parsed YYYY-MM-DD format: $birthYear");
    }
    // YYYY/MM/DD 형식
    else if (preg_match('/(\d{4})\/\d{2}\/\d{2}/', $cleanData, $matches)) {
        $birthYear = intval($matches[1]);
        error_log("Parsed YYYY/MM/DD format: $birthYear");
    }
    // YYYY년 형식
    else if (preg_match('/(\d{4})년/', $cleanData, $matches)) {
        $birthYear = intval($matches[1]);
        error_log("Parsed YYYY년 format: $birthYear");
    }
    // 4자리 숫자만 (앞뒤 다른 문자 있어도 추출)
    else if (preg_match('/(\d{4})/', $cleanData, $matches)) {
        $birthYear = intval($matches[1]);
        error_log("Parsed 4-digit number: $birthYear");
    }
    // 그냥 숫자인 경우
    else if (is_numeric($cleanData)) {
        $numericValue = intval($cleanData);
        if ($numericValue >= 1900 && $numericValue <= 2030) {
            $birthYear = $numericValue;
            error_log("Parsed numeric value: $birthYear");
        }
    }
    
    // 유효성 검사
    if ($birthYear < 1900 || $birthYear > 2020) {
        error_log("Invalid birth year: $birthYear, resetting to 0");
        $birthYear = 0;
    }
}

// 디버깅용 - 나중에 제거
error_log("Parsed birth year: " . $birthYear);

$grade = $birthYear > 0 ? calculateGrade($birthYear) : '';
$semester = calculateSemester();

// 디버깅용 - 나중에 제거
error_log("Calculated grade: " . $grade);
error_log("Final grade value: " . $grade);
error_log("School value: " . $school);

// 기존 alt42t 데이터 가져오기
// 먼저 alt42t_users에서 사용자 정보 가져오기
$alt42tUser = $DB->get_record('alt42t_users', array('userid' => $userid));

$existingData = null;
if ($alt42tUser) {
    // 사용자가 있으면 관련 데이터 조회
    $existingData = $DB->get_record_sql("
        SELECT u.*, e.exam_type, ed.start_date, ed.end_date, ed.math_date as math_exam_date, 
               er.tip_text as exam_scope, ed.status as exam_status, ss.status as study_status
        FROM {alt42t_users} u
        LEFT JOIN {alt42t_exams} e ON u.school_name = e.school_name AND u.grade = e.grade
        LEFT JOIN {alt42t_exam_dates} ed ON e.exam_id = ed.exam_id AND u.id = ed.user_id
        LEFT JOIN {alt42t_exam_resources} er ON e.exam_id = er.exam_id AND u.id = er.user_id
        LEFT JOIN {alt42t_study_status} ss ON u.id = ss.user_id AND e.exam_id = ss.exam_id
        WHERE u.userid = ?
        ORDER BY u.timemodified DESC
        LIMIT 1", array($userid));
    
    // 데이터가 있으면 alt42t 정보 가져오기 (mathking DB 정보가 우선)
    if ($existingData) {
        // mathking DB에 정보가 없을 때만 alt42t 정보 사용
        $school = $school ?: $existingData->school_name;
        
        // mathking DB에서 학년을 계산하지 못한 경우에만 alt42t의 grade 사용
        if (empty($grade) && $existingData->grade) {
            $gradeNum = $existingData->grade;
            
            // 학년 숫자를 문자열로 변환
            if ($gradeNum >= 1 && $gradeNum <= 3) {
                // 학교명으로 학교급 판단
                if (strpos($school, '고등') !== false || strpos($school, '고교') !== false) {
                    $grade = '고등학교 ' . $gradeNum . '학년';
                } else if (strpos($school, '중학') !== false) {
                    $grade = '중학교 ' . $gradeNum . '학년';
                } else {
                    // 기본값은 고등학교로
                    $grade = '고등학교 ' . $gradeNum . '학년';
                }
            }
        }
    }
}

// 디버깅용
error_log('exam_preparation_system.php - userid: ' . $userid);
error_log('exam_preparation_system.php - alt42tUser: ' . print_r($alt42tUser, true));
error_log('exam_preparation_system.php - existingData: ' . print_r($existingData, true));

// 시험 종류와 날짜 기본값 설정
$examTypeInfo = calculateExamTypeAndDate();
$defaultExamType = $examTypeInfo['type'];
$defaultExamDate = $examTypeInfo['date'];

// 같은 학교 시험 정보 가져오기
$schoolExamData = array();
if ($school) {
    $schoolExams = $DB->get_records_sql("
        SELECT DISTINCT ed.start_date, ed.end_date, ed.math_date as math_exam_date, 
               er.tip_text as exam_scope, e.exam_type
        FROM {alt42t_exams} e
        JOIN {alt42t_exam_dates} ed ON e.exam_id = ed.exam_id
        LEFT JOIN {alt42t_exam_resources} er ON e.exam_id = er.exam_id
        WHERE e.school_name = ? 
        AND ed.start_date >= CURDATE()
        ORDER BY ed.start_date DESC
        LIMIT 5", array($school));
    
    foreach ($schoolExams as $exam) {
        $schoolExamData[] = array(
            'examType' => $exam->exam_type,
            'startDate' => $exam->start_date,
            'endDate' => $exam->end_date,
            'mathDate' => $exam->math_exam_date,
            'scope' => str_replace('시험 범위: ', '', $exam->exam_scope ?? '')
        );
    }
}

// D-Day 계산 함수
function calculateDday($examDate) {
    if (!$examDate) return '';
    
    $today = new DateTime();
    $exam = new DateTime($examDate);
    $interval = $today->diff($exam);
    $days = $interval->days;
    
    if ($interval->invert == 0) {
        return $days == 0 ? 'D-Day' : 'D-' . $days;
    } else {
        return 'D+' . $days;
    }
}

// 세션에 현재 단계 저장
if (!isset($_SESSION['exam_step'])) {
    $_SESSION['exam_step'] = 0;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>시험 대비 에이전트</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #EBF3FF 0%, #E3E9FF 100%);
            min-height: 100vh;
        }
        
        /* 헤더 스타일 */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            padding: 1rem 1.5rem;
        }
        
        .header-content {
            max-width: 1024px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .dashboard-btn {
            background: #4F46E5;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .dashboard-btn:hover {
            background: #3730A3;
            color: white;
            text-decoration: none;
        }
        
        .header-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .header-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4F46E5;
        }
        
        .header-user-info {
            font-size: 0.875rem;
            color: #6B7280;
            margin-left: 0;
            line-height: 1.5;
        }
        
        .header-user-info span {
            font-weight: 500;
            color: #333;
        }
        
        #headerDday {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .progress-dots {
            display: flex;
            gap: 0.5rem;
        }
        
        .progress-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #E5E7EB;
            transition: background 0.3s;
        }
        
        .progress-dot.active {
            background: #4F46E5;
        }
        
        /* 메인 컨테이너 */
        .main-container {
            padding-top: 5rem;
            min-height: 100vh;
        }
        
        .step-container {
            min-height: 100vh;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .step-container.active {
            display: flex;
        }
        
        .card {
            max-width: 640px;
            width: 100%;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .card-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .card-title {
            font-size: 1.875rem;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .card-subtitle {
            color: #6B7280;
        }
        
        /* 폼 스타일 */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }
        
        /* 버튼 스타일 */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #4F46E5;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4338CA;
        }
        
        .btn-secondary {
            background: #6B7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4B5563;
        }
        
        .btn-success {
            background: #10B981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-warning {
            background: #F59E0B;
            color: white;
        }
        
        .btn-warning:hover {
            background: #D97706;
        }
        
        .btn-purple {
            background: #8B5CF6;
            color: white;
        }
        
        .btn-purple:hover {
            background: #7C3AED;
        }
        
        .btn-fullwidth {
            width: 100%;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
        }
        
        /* 상태 표시 */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-expected {
            background: #FEF3C7;
            color: #92400E;
        }
        
        .status-confirmed {
            background: #D1FAE5;
            color: #065F46;
        }
        
        /* 같은 학교 시험 정보 */
        .collapsible {
            border-top: 1px solid #E5E7EB;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .collapsible-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6B7280;
            font-size: 0.875rem;
            cursor: pointer;
            margin-bottom: 1rem;
        }
        
        .collapsible-content {
            display: none;
        }
        
        .collapsible-content.show {
            display: block;
        }
        
        .exam-info-item {
            padding: 0.75rem;
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .exam-info-item:hover {
            background: #F9FAFB;
        }
        
        .exam-info-date {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1F2937;
        }
        
        .exam-info-details {
            font-size: 0.75rem;
            color: #6B7280;
            margin-top: 0.25rem;
        }
        
        /* 전략 이해 페이지 스타일 */
        .strategy-box {
            background: linear-gradient(135deg, #FEF3C7 0%, #FED7AA 100%);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }
        
        .strategy-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #92400E;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .strategy-subtitle {
            font-size: 1.125rem;
            color: #B45309;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .strategy-description {
            color: #92400E;
            margin-bottom: 1.5rem;
        }
        
        .strategy-info-box {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .strategy-info-title {
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .strategy-info-text {
            color: #374151;
        }
        
        .strategy-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            text-align: center;
        }
        
        .strategy-feature {
            background: white;
            border-radius: 0.5rem;
            padding: 0.75rem;
        }
        
        .strategy-feature-icon {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .strategy-feature-text {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1F2937;
        }
        
        .strategy-content {
            margin-top: 1rem;
        }
        
        .strategy-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
        }
        
        .strategy-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            background: #4F46E5;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .strategy-text h4 {
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.25rem;
        }
        
        .strategy-text p {
            color: #6B7280;
            font-size: 0.875rem;
        }
        
        .tips-box {
            background: #F3F4F6;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .tips-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tips-list {
            list-style: none;
            padding: 0;
        }
        
        .tips-list li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
            color: #6B7280;
        }
        
        .tips-list li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: #4F46E5;
            font-weight: bold;
        }
        
        /* 단계 선택 스타일 */
        .level-option {
            padding: 1.5rem;
            border: 2px solid #E5E7EB;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .level-option:hover {
            border-color: #A78BFA;
        }
        
        .level-option.selected {
            border-color: #4F46E5;
            background: #EEF2FF;
        }
        
        .level-option-content {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .level-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }
        
        .level-info {
            flex: 1;
        }
        
        .level-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .level-description {
            color: #6B7280;
            margin-bottom: 0.75rem;
        }
        
        .level-guide {
            background: #F3F4F6;
            border-radius: 0.5rem;
            padding: 0.75rem;
        }
        
        .level-guide-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .level-guide-text {
            font-size: 0.875rem;
            color: #6B7280;
        }
        
        .level-check {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: #4F46E5;
            font-size: 1.5rem;
            display: none;
        }
        
        .level-option.selected .level-check {
            display: block;
        }
        
        /* 라스트 청킹 설명 스타일 추가 */
        .strategy-intro {
            background: #f7fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 4px solid #4299e1;
        }

        .strategy-intro p {
            margin: 0;
            font-size: 1.1em;
            line-height: 1.7;
            color: #2d3748;
        }

        .strategy-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .wrong-method, .right-method {
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .wrong-method {
            background: #fed7d7;
            border: 2px solid #feb2b2;
        }

        .right-method {
            background: #c6f6d5;
            border: 2px solid #9ae6b4;
        }

        .wrong-method h3, .right-method h3 {
            margin: 0 0 10px 0;
            font-size: 1.2em;
        }

        .method-subtitle {
            font-weight: 600;
            margin: 0 0 15px 0;
            color: #2d3748;
        }

        .wrong-method ul, .right-method ul {
            text-align: left;
            margin: 15px 0;
            padding-left: 20px;
            list-style: disc;
        }

        .wrong-method li, .right-method li {
            margin-bottom: 8px;
            color: #2d3748;
        }

        .result {
            font-style: italic;
            font-weight: 600 !important;
            color: #2d3748;
            margin-top: 15px;
        }

        .strategy-principle {
            margin-bottom: 25px;
        }

        .strategy-principle h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .principle-box {
            background: linear-gradient(135deg, #fef5e7, #fed7aa);
            border: 2px solid #f6ad55;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-size: 1.1em;
            line-height: 1.7;
        }

        .principle-box strong {
            color: #c05621;
            font-size: 1.2em;
            display: block;
            margin-bottom: 10px;
        }

        .strategy-evidence {
            margin-bottom: 25px;
        }

        .strategy-evidence h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .strategy-evidence ul {
            background: #f0fff4;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #48bb78;
            list-style: disc;
            padding-left: 40px;
        }

        .strategy-evidence li {
            margin-bottom: 10px;
            line-height: 1.6;
            color: #2d3748;
        }

        .source {
            font-size: 0.9em;
            color: #4a5568;
            font-style: italic;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .strategy-steps h3 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 1.3em;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .step-card {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            border-radius: 12px;
            background: rgba(235, 248, 255, 0.8);
            border: 2px solid #63b3ed;
            transition: all 0.3s ease;
        }

        .step-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .step-number {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #4299e1;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1em;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .step-content h4 {
            margin: 0 0 5px 0;
            color: #2d3748;
            font-size: 1em;
            font-weight: 600;
        }

        .step-content p {
            margin: 0;
            color: #4a5568;
            font-size: 0.9em;
            line-height: 1.4;
        }

        /* 펼치기/접기 스타일 */
        .strategy-collapsible {
            margin-bottom: 20px;
        }

        .strategy-collapsible-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f7fafc;
            padding: 15px 20px;
            border-radius: 12px;
            cursor: pointer;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            user-select: none;
        }

        .strategy-collapsible-header:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
        }

        .strategy-collapsible-header.active {
            background: #edf2f7;
            border-color: #4299e1;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .strategy-collapsible-header h3 {
            margin: 0;
            color: #2d3748;
            font-size: 1.2em;
        }

        .collapse-icon {
            font-size: 1.2em;
            color: #4a5568;
            transition: transform 0.3s ease;
        }

        .collapse-icon.rotated {
            transform: rotate(180deg);
        }

        .strategy-collapsible-content {
            border: 2px solid #e2e8f0;
            border-top: none;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            padding: 20px;
            background: white;
            animation: slideDown 0.3s ease;
        }

        .evidence-list {
            background: #f0fff4;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #48bb78;
            list-style: disc;
            padding-left: 40px;
            margin: 0;
        }

        .evidence-list li {
            margin-bottom: 10px;
            line-height: 1.6;
            color: #2d3748;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 오디오 플레이어 스타일 */
        .audio-player-container {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }

        .audio-player-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 1.1em;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .audio-icon {
            font-size: 1.3em;
        }

        .strategy-audio {
            width: 100%;
            max-width: 400px;
            height: 48px;
            outline: none;
        }

        .strategy-audio::-webkit-media-controls-panel {
            background-color: white;
        }

        .audio-note {
            margin-top: 12px;
            font-size: 0.9em;
            color: #4a5568;
            font-style: italic;
        }

        /* 반응형 */
        @media (max-width: 640px) {
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
            
            .strategy-features {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .dashboard-btn {
                font-size: 12px;
                padding: 6px 12px;
            }
            
            .strategy-comparison {
                grid-template-columns: 1fr;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* 아이콘 스타일 */
        .icon {
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: middle;
        }
        
        /* 애니메이션 */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .step-container.active .card {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</head>
<body>
    <!-- 헤더 -->
    <div class="header">
        <div class="header-content">
            <div class="header-info">
                <h1 class="header-title">시험 대비 에이전트</h1>
                <div class="header-user-info" id="headerUserInfo" style="<?php echo ($fullname || $school || $grade) ? 'display: block;' : 'display: none;'; ?>">
                    <span id="headerUserName"><?php echo htmlspecialchars($fullname); ?></span><?php if($school): ?> | 
                    <span id="headerSchool"><?php echo htmlspecialchars($school); ?></span><?php endif; ?><?php if($grade): ?> | 
                    <span id="headerGrade"><?php echo htmlspecialchars($grade); ?></span><?php endif; ?> | 
                    <span id="headerExamType"><?php echo htmlspecialchars(($existingData && $existingData->exam_type) ? $existingData->exam_type : $defaultExamType); ?></span><?php 
                    $displayDate = ($existingData && $existingData->math_exam_date) ? $existingData->math_exam_date : $defaultExamDate;
                    $dday = calculateDday($displayDate);
                    if($dday): ?> | 
                    수학 시험 <span id="headerDday"><?php echo $dday; ?></span><?php endif; ?>
                </div>
            </div>
            <div class="header-actions">
                <div class="progress-dots">
                    <div class="progress-dot active"></div>
                    <div class="progress-dot"></div>
                    <div class="progress-dot"></div>
                    <div class="progress-dot"></div>
                    <div class="progress-dot"></div>
                </div>
                <a href="dashboard.php?userid=<?php echo $userid; ?>" class="dashboard-btn" title="대시보드로 이동">
                    📊 대시보드
                </a>
            </div>
        </div>
    </div>
    
    <!-- PHP 변수 확인용 (숨김) -->
    <div style="display:none" id="php-debug-info">
        <p>PHP Grade: <?php echo htmlspecialchars($grade); ?></p>
        <p>PHP School: <?php echo htmlspecialchars($school); ?></p>
        <p>PHP Birth Year: <?php echo $birthYear; ?></p>
    </div>
    
    <div class="main-container">
        <!-- Step 1: 정보입력 -->
        <div class="step-container active" id="step-0">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">🏫</div>
                    <h2 class="card-title">정보입력</h2>
                    <p class="card-subtitle">시험 대비를 위한 기본 정보를 입력해주세요</p>
                </div>
                
                <form id="infoForm">
                    <div class="form-group">
                        <label class="form-label">이름</label>
                        <input type="text" class="form-input" id="name" value="<?php echo htmlspecialchars($fullname); ?>" placeholder="이름을 입력하세요" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">학교</label>
                        <input type="text" class="form-input" id="school" value="<?php echo htmlspecialchars($school); ?>" placeholder="학교명을 입력하세요" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">학년</label>
                            <select class="form-select" id="grade" required>
                                <option value="">선택하세요</option>
                                <option value="고등학교 3학년" <?php echo $grade == '고등학교 3학년' ? 'selected' : ''; ?>>고등학교 3학년</option>
                                <option value="고등학교 2학년" <?php echo $grade == '고등학교 2학년' ? 'selected' : ''; ?>>고등학교 2학년</option>
                                <option value="고등학교 1학년" <?php echo $grade == '고등학교 1학년' ? 'selected' : ''; ?>>고등학교 1학년</option>
                                <option value="중학교 3학년" <?php echo $grade == '중학교 3학년' ? 'selected' : ''; ?>>중학교 3학년</option>
                                <option value="중학교 2학년" <?php echo $grade == '중학교 2학년' ? 'selected' : ''; ?>>중학교 2학년</option>
                                <option value="중학교 1학년" <?php echo $grade == '중학교 1학년' ? 'selected' : ''; ?>>중학교 1학년</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">학기</label>
                            <select class="form-select" id="semester" required>
                                <option value="1학기" <?php echo $semester == '1학기' ? 'selected' : ''; ?>>1학기</option>
                                <option value="2학기" <?php echo $semester == '2학기' ? 'selected' : ''; ?>>2학기</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-primary btn-fullwidth" onclick="nextStep()">
                        다음으로 <span>→</span>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Step 2: 시험설정 -->
        <div class="step-container" id="step-1">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">📅</div>
                    <h2 class="card-title">시험설정</h2>
                    <p class="card-subtitle">시험 일정과 범위를 설정해주세요</p>
                </div>
                
                <form id="examForm">
                    <div class="form-group">
                        <label class="form-label">시험 종류</label>
                        <select class="form-select" id="examType" required>
                            <option value="1학기 중간고사">1학기 중간고사</option>
                            <option value="1학기 기말고사">1학기 기말고사</option>
                            <option value="2학기 중간고사">2학기 중간고사</option>
                            <option value="2학기 기말고사">2학기 기말고사</option>
                        </select>
                    </div>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label class="form-label">시험 시작일</label>
                            <input type="date" class="form-input" id="examStartDate" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">시험 종료일</label>
                            <input type="date" class="form-input" id="examEndDate">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">수학 시험일</label>
                            <input type="date" class="form-input" id="mathExamDate">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">시험 범위</label>
                        <textarea class="form-textarea" id="examScope" rows="3" placeholder="시험 범위를 입력하세요"></textarea>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="font-size: 0.875rem; color: #6B7280;">
                            현재 상태: 
                            <span class="status-badge status-expected" id="examStatus">예상</span>
                        </span>
                        <button type="button" onclick="toggleExamStatus()" style="font-size: 0.875rem; color: #4F46E5; background: none; border: none; cursor: pointer;">
                            상태 변경
                        </button>
                    </div>
                    
                    <?php if (!empty($schoolExamData)): ?>
                    <div class="collapsible">
                        <div class="collapsible-header" onclick="toggleCollapsible()">
                            <span>▼</span>
                            같은 학교 시험 정보 보기
                        </div>
                        <div class="collapsible-content" id="schoolExamInfo">
                            <?php foreach ($schoolExamData as $exam): ?>
                            <div class="exam-info-item" onclick='selectExamInfo(<?php echo json_encode($exam); ?>)'>
                                <div class="exam-info-date">
                                    <?php echo $exam['startDate']; ?> ~ <?php echo $exam['endDate']; ?>
                                </div>
                                <div class="exam-info-details">
                                    수학: <?php echo $exam['mathDate']; ?> | 범위: <?php echo htmlspecialchars($exam['scope']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="openSchoolHomepage()" style="flex: 1;">
                            🌐 학교 홈페이지
                        </button>
                        <button type="button" class="btn btn-success" onclick="saveAndNext()" style="flex: 1;">
                            다음으로 <span>→</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Step 3: 단계선택 -->
        <div class="step-container" id="step-2">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">🎯</div>
                    <h2 class="card-title">단계선택</h2>
                    <p class="card-subtitle">너의 시작 위치는?</p>
                    <p style="font-size: 0.875rem; color: #6B7280; margin-top: 0.5rem;">
                        시작점을 선택해 주세요! 너에게 맞는 출발선을 찾아보자! 🚀
                    </p>
                </div>
                
                <div class="level-option" onclick="selectLevel('concept')">
                    <div class="level-option-content">
                        <div class="level-icon">📚</div>
                        <div class="level-info">
                            <h3 class="level-title">개념공부</h3>
                            <p class="level-description">기본 개념부터 차근차근 시작해요</p>
                            <div class="level-guide">
                                <p class="level-guide-title">📋 상세 가이드</p>
                                <p class="level-guide-text">
                                    시험 기간이라면 완벽한 개념 학습보다는 핵심 개념을 빠르게 정리하는 것이 중요해요. 
                                    기본 개념만 확실히 잡고 문제 풀이로 넘어가세요.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="level-check">✓</div>
                </div>
                
                <div class="level-option" onclick="selectLevel('review')">
                    <div class="level-option-content">
                        <div class="level-icon">🧠</div>
                        <div class="level-info">
                            <h3 class="level-title">개념복습</h3>
                            <p class="level-description">배운 개념들을 다시 정리하고 복습해요</p>
                            <div class="level-guide">
                                <p class="level-guide-title">📋 상세 가이드</p>
                                <p class="level-guide-text">
                                    이미 배운 개념들을 빠르게 복습하는 단계예요.<br>
                                    • 기억이 안 나면: 개념 다시 정리<br>
                                    • 어느 정도 기억나면: 유형 테스트 3회 → 단원 테스트 90점<br>
                                    • 개념이 잡혀있으면: 바로 단원 테스트 90점 도전
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="level-check">✓</div>
                </div>
                
                <div class="level-option" onclick="selectLevel('practice')">
                    <div class="level-option-content">
                        <div class="level-icon">✏️</div>
                        <div class="level-info">
                            <h3 class="level-title">유형공부</h3>
                            <p class="level-description">다양한 문제 유형들을 학습해요</p>
                            <div class="level-guide">
                                <p class="level-guide-title">📋 상세 가이드</p>
                                <p class="level-guide-text">
                                    mathking 내신테스트로 시작해서 중급→심화 유형 순서로 학습하세요. 
                                    교재 문제와 병행하며 마지막에 기출문제까지 완주!
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="level-check">✓</div>
                </div>
                
                <button type="button" class="btn btn-primary btn-fullwidth" onclick="saveStudyLevelAndNext()" style="margin-top: 1.5rem;" disabled id="levelNextBtn">
                    다음으로 <span>→</span>
                </button>
            </div>
        </div>
        
        <!-- Step 4: 전략이해 -->
        <div class="step-container" id="step-3">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">📚</div>
                    <h2 class="card-title">전략이해</h2>
                    <p class="card-subtitle">라스트 청킹 학습법 소개</p>
                </div>
                
                <!-- 오디오 플레이어 -->
                <div class="audio-player-container">
                    <div class="audio-player-title">
                        <span class="audio-icon">🎧</span>
                        <span>라스트 청킹 설명 듣기</span>
                    </div>
                    <audio id="strategyAudio" class="strategy-audio" controls>
                        <source src="audio/last_chunking_intro.m4a" type="audio/mp4">
                        <source src="audio/last_chunking_intro.mp3" type="audio/mpeg">
                        브라우저가 오디오 재생을 지원하지 않습니다.
                    </audio>
                    <div class="audio-note">
                        ※ 음성으로 편하게 들으며 이해해보세요
                    </div>
                </div>
                
                <!-- 라스트 청킹 소개 -->
                <div class="strategy-intro">
                    <p>라스트 청킹(Last Chunking)은 시험 직전, 새로운 내용을 공부하는 대신 <strong>이미 배운 내용을 빠르게 인출할 수 있도록 뇌를 재배선</strong>하는 과학적 학습법입니다.</p>
                </div>
                
                <!-- 비교 섹션 -->
                <div class="strategy-comparison">
                    <div class="wrong-method">
                        <h3>❌ 일반적인 방법</h3>
                        <p class="method-subtitle">"아직 모르는 게 많아!"</p>
                        <ul>
                            <li>시험 직전까지 새로운 내용 학습</li>
                            <li>완벽하지 않은 부분에 매달리기</li>
                            <li>친구들과 새로운 정보 교환</li>
                        </ul>
                        <p class="result">→ 정보 과부하, 혼란 증가</p>
                    </div>
                    <div class="right-method">
                        <h3>✅ 라스트 청킹</h3>
                        <p class="method-subtitle">"배운 걸 확실하게!"</p>
                        <ul>
                            <li>기존 지식의 인출 연습에 집중</li>
                            <li>핵심 내용 반복으로 자동화</li>
                            <li>체계적인 5일 단계별 실행</li>
                        </ul>
                        <p class="result">→ 빠른 기억 인출, 자신감 향상</p>
                    </div>
                </div>

                <!-- 핵심 원리 -->
                <div class="strategy-collapsible">
                    <div class="strategy-collapsible-header" onclick="toggleStrategySection('principle')">
                        <h3>🎯 핵심 원리</h3>
                        <span class="collapse-icon" id="principle-icon">▼</span>
                    </div>
                    <div class="strategy-collapsible-content" id="principle-content" style="display: none;">
                        <div class="principle-box">
                            <strong>장기기억 → 작업기억 이동</strong><br>
                            장기기억에 저장된 지식을 시험 상황에서 즉시 사용할 수 있도록<br>
                            '작업기억'으로 빠르게 이동시키는 것이 목표입니다.
                        </div>
                    </div>
                </div>

                <!-- 과학적 근거 -->
                <div class="strategy-collapsible">
                    <div class="strategy-collapsible-header" onclick="toggleStrategySection('evidence')">
                        <h3>🔬 과학적 근거</h3>
                        <span class="collapse-icon" id="evidence-icon">▼</span>
                    </div>
                    <div class="strategy-collapsible-content" id="evidence-content" style="display: none;">
                        <ul class="evidence-list">
                            <li><strong>인출 연습 효과:</strong> 재학습 대비 장기 기억 보존율 50% 향상</li>
                            <li><strong>테스트 효과:</strong> 문제 풀이가 단순 재독보다 학습 효과 2배</li>
                            <li><strong>간격 효과:</strong> 24시간-3일-5일 간격 반복시 기억 정착률 80% 증가</li>
                        </ul>
                        <div class="source">출처: Roediger, H. L. (2011). Applying Cognitive Psychology to Education</div>
                    </div>
                </div>

                <!-- 6단계 실행 과정 -->
                <div class="strategy-collapsible">
                    <div class="strategy-collapsible-header" onclick="toggleStrategySection('steps')">
                        <h3>📅 6단계 실행 과정</h3>
                        <span class="collapse-icon" id="steps-icon">▼</span>
                    </div>
                    <div class="strategy-collapsible-content" id="steps-content" style="display: none;">
                        <div class="steps-grid">
                            <div class="step-card">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>D-5: 전체 현황 파악</h4>
                                    <p>시험 정보 수집, 기출문제 수집, 전체 계획 수립</p>
                                </div>
                            </div>
                            
                            <div class="step-card">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>D-4: 기출 패턴 분석</h4>
                                    <p>출제 경향 파악, 빈출 유형 분석, 우선순위 설정</p>
                                </div>
                            </div>
                            
                            <div class="step-card">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>D-3: 핵심 내용 정리</h4>
                                    <p>개념 압축, 공식 정리, 암기법 개발</p>
                                </div>
                            </div>
                            
                            <div class="step-card">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>D-2: 집중 학습</h4>
                                    <p>반복 학습, 오답 분석, 인출 연습</p>
                                </div>
                            </div>
                            
                            <div class="step-card">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h4>D-1: 약점 보완</h4>
                                    <p>약점 집중 공략, 최종 점검, 심리적 준비</p>
                                </div>
                            </div>
                            
                            <div class="step-card">
                                <div class="step-number">6</div>
                                <div class="step-content">
                                    <h4>D-Day: 시험 당일</h4>
                                    <p>가벼운 복습, 컨디션 관리, 자신감 충전</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="btn-group" style="margin-top: 1.5rem;">
                    <button type="button" class="btn btn-purple" onclick="window.location.href='last_chunking.php?userid=<?php echo $userid; ?>';" style="flex: 1;">
                        라스트 청킹 시작 🚀
                    </button>
                    <button type="button" class="btn btn-primary" onclick="goToDashboard()" style="flex: 1;">
                        대시보드로 이동 <span>→</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Step 5: 시작하기 -->
        <div class="step-container" id="step-4">
            <div class="card">
                <div class="card-header">
                    <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #4F46E5 0%, #8B5CF6 100%); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 2rem;">🚀</span>
                    </div>
                    <h2 class="card-title">시작하기</h2>
                    <p class="card-subtitle">모든 설정이 완료되었습니다!</p>
                </div>
                
                <div style="text-align: center; padding: 2rem 0;">
                    <p style="font-size: 1.125rem; color: #6B7280; margin-bottom: 2rem;">
                        시험 대비 학습을 시작할 준비가 완료되었습니다.<br>
                        대시보드에서 맞춤형 학습 계획을 확인하세요!
                    </p>
                    
                    <button type="button" class="btn btn-primary" onclick="window.location.href='dashboard.php?userid=<?php echo $userid; ?>';" style="font-size: 1.125rem; padding: 1rem 2rem;">
                        대시보드로 이동하기 🚀
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // 전역 변수
    let currentStep = 0;
    let formData = {
        userid: <?php echo $userid; ?>,
        name: '<?php echo addslashes($fullname); ?>',
        school: '<?php echo addslashes($school); ?>',
        grade: '<?php echo addslashes($grade); ?>',
        semester: '<?php echo addslashes($semester); ?>',
        examType: '',
        examStartDate: '',
        examEndDate: '',
        mathExamDate: '',
        examScope: '',
        examStatus: 'expected',
        studyLevel: ''
    };
    
    // 초기화
    document.addEventListener('DOMContentLoaded', function() {
        // 시험 대비 에이전트 DB 정보 디버깅
        console.log('=== 시험 대비 에이전트 DB 정보 ===');
        console.log('이름: <?php echo htmlspecialchars($fullname); ?>');
        console.log('학교 (fieldid 88): <?php echo htmlspecialchars($school); ?>');
        console.log('출생년도 (fieldid 89): <?php echo $birthYear; ?>');
        console.log('계산된 학년: <?php echo htmlspecialchars($grade); ?>');
        console.log('학기: <?php echo $semester; ?>');
        
        // 기존 데이터가 있으면 채우기
        <?php if ($existingData): ?>
        console.log('=== Alt42t DB 기존 데이터 ===');
        console.log('Existing data found:', <?php echo json_encode($existingData); ?>);
        formData.name = '<?php echo addslashes($fullname ?: ($existingData->name ?? '')); ?>';
        formData.school = '<?php echo addslashes($school ?: ($existingData->school_name ?? '')); ?>';
        formData.grade = '<?php echo addslashes($grade); ?>';
        formData.examType = '<?php echo addslashes($existingData->exam_type ?? ''); ?>';
        formData.examStartDate = '<?php echo $existingData->start_date ?? ''; ?>';
        formData.examEndDate = '<?php echo $existingData->end_date ?? ''; ?>';
        formData.mathExamDate = '<?php echo $existingData->math_exam_date ?? ''; ?>';
        formData.examScope = '<?php echo addslashes(str_replace('시험 범위: ', '', $existingData->exam_scope ?? '')); ?>';
        formData.examStatus = '<?php echo $existingData->exam_status ?? 'expected'; ?>';
        currentExamStatus = '<?php echo $existingData->exam_status ?? 'expected'; ?>';
        formData.studyLevel = '<?php echo $existingData->study_status ?? ''; ?>';
        <?php else: ?>
        console.log('No existing alt42t data found for user <?php echo $userid; ?>');
        
        // 시험 대비 에이전트 DB 정보로 초기화
        formData.name = '<?php echo addslashes($fullname); ?>';
        formData.school = '<?php echo addslashes($school); ?>';
        formData.grade = '<?php echo addslashes($grade); ?>';
        <?php endif; ?>
        
        // 폼에 데이터 채우기 (이미 HTML에서 value로 설정되어 있음)
        console.log('Form data:', formData);
        
        // 자동으로 시험 종류와 날짜 설정
        const examInfo = <?php echo json_encode(calculateExamTypeAndDate()); ?>;
        if (!formData.examType) {
            formData.examType = examInfo.type;
        }
        if (!formData.examStartDate) {
            formData.examStartDate = examInfo.date;
        }
        
        // 초기 D-Day 설정 - 기존 데이터나 기본값 사용
        if (!formData.mathExamDate && examInfo.date) {
            formData.mathExamDate = examInfo.date;
        }
        
        // 초기 헤더 정보 업데이트
        updateHeaderInfo();
    });
    
    // 다음 단계로 이동
    function nextStep() {
        if (validateCurrentStep()) {
            // 정보입력 단계에서는 저장 후 다음으로
            if (currentStep === 0) {
                saveCurrentStepData();
                saveToDatabase(() => {
                    currentStep++;
                    showStep(currentStep);
                    updateProgressDots();
                    updateHeaderInfo();
                });
            } else {
                saveCurrentStepData();
                currentStep++;
                showStep(currentStep);
                updateProgressDots();
                updateHeaderInfo();
            }
        }
    }
    
    // 현재 단계 데이터 검증
    function validateCurrentStep() {
        if (currentStep === 0) {
            const name = document.getElementById('name').value.trim();
            const school = document.getElementById('school').value.trim();
            const grade = document.getElementById('grade').value;
            
            if (!name || !school || !grade) {
                alert('모든 정보를 입력해주세요.');
                return false;
            }
        }
        return true;
    }
    
    // 현재 단계 데이터 저장
    function saveCurrentStepData() {
        if (currentStep === 0) {
            formData.name = document.getElementById('name').value.trim();
            formData.school = document.getElementById('school').value.trim();
            formData.grade = document.getElementById('grade').value;
            formData.semester = document.getElementById('semester').value;
        }
    }
    
    // 단계 표시
    function showStep(step) {
        // 모든 단계 숨기기
        document.querySelectorAll('.step-container').forEach(container => {
            container.classList.remove('active');
        });
        
        // 현재 단계 표시
        document.getElementById(`step-${step}`).classList.add('active');
        
        // 시험 설정 단계일 때 초기값 설정
        if (step === 1) {
            document.getElementById('examType').value = formData.examType;
            document.getElementById('examStartDate').value = formData.examStartDate;
            document.getElementById('examEndDate').value = formData.examEndDate;
            document.getElementById('mathExamDate').value = formData.mathExamDate;
            document.getElementById('examScope').value = formData.examScope;
            updateExamStatusBadge();
        }
        
        // 단계 선택일 때 선택된 레벨 표시 (Step 3이 이제 단계선택)
        if (step === 2 && formData.studyLevel) {
            selectLevel(formData.studyLevel, false);
        }
    }
    
    // 진행 표시 업데이트
    function updateProgressDots() {
        const dots = document.querySelectorAll('.progress-dot');
        dots.forEach((dot, index) => {
            if (index <= currentStep) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }
    
    // 헤더 정보 업데이트
    function updateHeaderInfo() {
        // 항상 업데이트 - currentStep 조건 제거
        if (formData.name || formData.school || formData.grade) {
            document.getElementById('headerUserInfo').style.display = 'block';
            if (formData.name) document.getElementById('headerUserName').textContent = formData.name;
            if (formData.school) document.getElementById('headerSchool').textContent = formData.school;
            if (formData.grade) document.getElementById('headerGrade').textContent = formData.grade;
            if (formData.examType) document.getElementById('headerExamType').textContent = formData.examType;
            
            // D-Day 업데이트 - mathExamDate가 있으면 업데이트
            if (formData.mathExamDate) {
                const ddayElement = document.getElementById('headerDday');
                if (ddayElement) {
                    ddayElement.textContent = calculateDday(formData.mathExamDate);
                }
            }
        }
    }
    
    // D-Day 계산
    function calculateDday(examDate) {
        if (!examDate) return 'D-Day';
        
        const today = new Date();
        const exam = new Date(examDate);
        const diffTime = exam - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays > 0) return `D-${diffDays}`;
        if (diffDays === 0) return 'D-Day';
        return `D+${Math.abs(diffDays)}`;
    }
    
    // 시험 상태 토글
    let currentExamStatus = '<?php echo $existingData->exam_status ?? 'expected'; ?>';
    function toggleExamStatus() {
        currentExamStatus = currentExamStatus === 'expected' ? 'confirmed' : 'expected';
        formData.examStatus = currentExamStatus;
        updateExamStatusBadge();
    }
    
    // 시험 상태 배지 업데이트
    function updateExamStatusBadge() {
        const badge = document.getElementById('examStatus');
        if (currentExamStatus === 'confirmed' || currentExamStatus === '확정') {
            badge.className = 'status-badge status-confirmed';
            badge.textContent = '확정';
            formData.examStatus = 'confirmed';
        } else {
            badge.className = 'status-badge status-expected';
            badge.textContent = '예상';
            formData.examStatus = 'expected';
        }
    }
    
    // 같은 학교 시험 정보 토글
    function toggleCollapsible() {
        const content = document.getElementById('schoolExamInfo');
        content.classList.toggle('show');
    }
    
    // 시험 정보 선택
    function selectExamInfo(examData) {
        document.getElementById('examStartDate').value = examData.startDate;
        document.getElementById('examEndDate').value = examData.endDate;
        document.getElementById('mathExamDate').value = examData.mathDate;
        document.getElementById('examScope').value = examData.scope;
        document.getElementById('schoolExamInfo').classList.remove('show');
    }
    
    // 학교 홈페이지 열기
    function openSchoolHomepage() {
        const school = formData.school;
        if (school) {
            window.open(`https://www.google.com/search?q=${encodeURIComponent(school)} 홈페이지`, '_blank');
        }
    }
    
    // 시험 설정 저장 후 다음으로
    function saveAndNext() {
        // 데이터 수집
        formData.examType = document.getElementById('examType').value;
        formData.examStartDate = document.getElementById('examStartDate').value;
        formData.examEndDate = document.getElementById('examEndDate').value;
        formData.mathExamDate = document.getElementById('mathExamDate').value;
        formData.examScope = document.getElementById('examScope').value;
        
        if (!formData.examStartDate) {
            alert('시험 시작일을 입력해주세요.');
            return;
        }
        
        console.log('Saving exam data:', formData);
        
        // 먼저 기본 정보 저장 (section 0)
        saveToDatabase(() => {
            // 그 다음 시험 정보 저장 (section 1)
            const examData = {
                userid: formData.userid,
                section: 1,
                startDate: formData.examStartDate,
                endDate: formData.examEndDate,
                mathExamDate: formData.mathExamDate,
                examScope: formData.examScope,
                status: formData.examStatus
            };
            
            console.log('Saving exam dates:', examData);
            
            fetch('save_exam_data_alt42t.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(examData)
            })
            .then(response => response.text())
            .then(text => {
                console.log('Exam save response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        nextStep();
                    } else {
                        alert('시험 정보 저장 실패: ' + data.message);
                        nextStep(); // 실패해도 진행
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    nextStep();
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                nextStep();
            });
        });
    }
    
    // 데이터베이스 저장
    function saveToDatabase(callback) {
        const saveData = {
            userid: formData.userid,
            section: 0,
            school: formData.school,
            grade: formData.grade, // 전체 문자열 전송
            examType: formData.examType,
            examStartDate: formData.examStartDate,
            examEndDate: formData.examEndDate,
            mathExamDate: formData.mathExamDate,
            examScope: formData.examScope,
            examStatus: formData.examStatus
        };
        
        console.log('Saving data:', saveData);
        
        fetch('save_exam_data_alt42t.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(saveData)
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    console.log('Save successful:', data);
                    if (callback) callback();
                } else {
                    console.error('Failed to save:', data.message);
                    // 더 구체적인 오류 메시지 표시
                    if (data.message && data.message.includes('데이터베이스 쓰기 오류')) {
                        alert('데이터 저장 중 오류가 발생했습니다.\n\n오류: ' + data.message + '\n\n페이지를 새로고침하고 다시 시도해주세요.');
                    } else {
                        alert('저장 실패: ' + data.message);
                    }
                    if (callback) callback(); // 실패해도 계속 진행
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                alert('서버 응답 오류');
                if (callback) callback();
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('네트워크 오류: ' + error.message);
            if (callback) callback(); // 오류가 나도 계속 진행
        });
    }
    
    // 단계 선택
    let selectedLevel = '';
    function selectLevel(level, save = true) {
        // 모든 옵션에서 선택 해제
        document.querySelectorAll('.level-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // 선택한 옵션 표시
        const options = document.querySelectorAll('.level-option');
        const levelMap = ['concept', 'review', 'practice'];
        const index = levelMap.indexOf(level);
        if (index !== -1) {
            options[index].classList.add('selected');
        }
        
        selectedLevel = level;
        if (save) {
            formData.studyLevel = level;
        }
        
        // 다음 버튼 활성화
        document.getElementById('levelNextBtn').disabled = false;
    }
    
    // 학습 단계 저장 후 다음으로
    function saveStudyLevelAndNext() {
        if (!formData.studyLevel) {
            alert('학습 단계를 선택해주세요.');
            return;
        }
        
        // 학습 단계 저장
        fetch('save_exam_data_alt42t.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                userid: formData.userid,
                section: 3,
                studyLevel: formData.studyLevel
            })
        })
        .then(response => response.json())
        .then(data => {
            nextStep();
        })
        .catch(error => {
            console.error('Error:', error);
            nextStep(); // 오류가 나도 계속 진행
        });
    }
    
    // 대시보드로 이동
    function goToDashboard() {
        window.location.href = 'dashboard.php?userid=' + formData.userid;
    }
    
    // 전략 섹션 펼치기/접기
    function toggleStrategySection(section) {
        const content = document.getElementById(section + '-content');
        const icon = document.getElementById(section + '-icon');
        const header = content.previousElementSibling;
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.classList.add('rotated');
            header.classList.add('active');
        } else {
            content.style.display = 'none';
            icon.classList.remove('rotated');
            header.classList.remove('active');
        }
    }
    </script>
    
    <!-- 디버깅 정보 (개발 중에만 표시) - 숨김 처리 -->
    <div style="display: none;">
        <h3>디버깅 정보</h3>
        <p><strong>User ID:</strong> <?php echo $userid; ?></p>
        <p><strong>Birth Data (raw from fieldid 89):</strong> "<?php echo htmlspecialchars($birthData); ?>"</p>
        <p><strong>Birth Data Type:</strong> <?php echo gettype($birthData); ?></p>
        <p><strong>Birth Data Length:</strong> <?php echo strlen($birthData); ?></p>
        <p><strong>Birth Year (parsed):</strong> <?php echo $birthYear; ?> (type: <?php echo gettype($birthYear); ?>)</p>
        <p><strong>Age (2025 - <?php echo $birthYear; ?>):</strong> <?php echo $birthYear > 0 ? (2025 - $birthYear) : 'N/A'; ?></p>
        <p><strong>Calculated Grade:</strong> <?php echo htmlspecialchars($grade); ?></p>
        <p><strong>School (fieldid 88):</strong> <?php echo htmlspecialchars($school); ?></p>
        
        <hr>
        <h4>학년 매핑 테스트:</h4>
        <?php
        if ($birthYear > 0) {
            echo "<p>출생년도 $birthYear → ";
            
            $testGradeMap = array(
                2007 => '고등학교 3학년',
                2008 => '고등학교 2학년',
                2009 => '고등학교 1학년',
                2010 => '중학교 3학년',
                2011 => '중학교 2학년',
                2012 => '중학교 1학년'
            );
            
            if (isset($testGradeMap[$birthYear])) {
                echo $testGradeMap[$birthYear];
            } else {
                echo "매핑 없음 (지원하는 출생년도: 2007-2012)";
            }
            echo "</p>";
        }
        ?>
        
        <h4>지원하는 출생년도:</h4>
        <ul>
            <li>2007년생 → 고등학교 3학년</li>
            <li>2008년생 → 고등학교 2학년</li>
            <li>2009년생 → 고등학교 1학년</li>
            <li>2010년생 → 중학교 3학년</li>
            <li>2011년생 → 중학교 2학년</li>
            <li>2012년생 → 중학교 1학년</li>
        </ul>
        
    </div>
</body>
</html>