<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// URL 파라미터에서 userid 가져오기
$userid = optional_param('userid', 0, PARAM_INT);

// userid가 없으면 현재 로그인한 사용자 ID 사용
if ($userid == 0) {
    $userid = $USER->id;
}  
 
// 사용자 정보 가져오기
$user = $DB->get_record('user', array('id' => $userid));
if (!$user) {
    print_error('User not found');
}

// 사용자의 기존 시험 정보 가져오기 (alt42t_ 테이블들에서)
require_once(__DIR__ . '/get_exam_data_alt42t.php');
$exam_info = getExamDataFromAlt42t($userid);

// LMS 데이터 가져오기 (mdl_user_info_data 테이블에서)
require_once(__DIR__ . '/get_user_lms_data.php');
$lms_data = getUserLMSData($userid);

// Calculate grade based on birth year
function calculateGrade($birthYear) {
    $currentYear = 2025;
    $age = $currentYear - $birthYear;
    
    switch($age) {
        case 18: return '고3';
        case 17: return '고2';
        case 16: return '고1';
        case 15: return '중3';
        case 14: return '중2';
        case 13: return '중1';
        default: return '';
    }
}

// Get birth year from user data
function getUserBirthYear($lms_data) {
    // birthdate 필드가 있으면 사용
    if (isset($lms_data['birthdate']) && $lms_data['birthdate']) {
        return (int)$lms_data['birthdate'];
    }
    return 2008; // 기본값
}


// Calculate current semester
function calculateSemester() {
    $currentMonth = date('n');
    return ($currentMonth >= 1 && $currentMonth <= 8) ? '1학기' : '2학기';
}

// Convert grade format
function convertGradeFormat($grade) {
    $gradeMap = [
        '고3' => '고등학교 3학년',
        '고2' => '고등학교 2학년',
        '고1' => '고등학교 1학년',
        '중3' => '중학교 3학년',
        '중2' => '중학교 2학년',
        '중1' => '중학교 1학년'
    ];
    return $gradeMap[$grade] ?? $grade;
}

// Calculate exam type and date
function calculateExamTypeAndDate() {
    $month = date('n');
    $day = date('j');
    
    if (($month == 12 && $day >= 11) || ($month >= 1 && $month <= 4) || ($month == 5 && $day == 1)) {
        return ['type' => '1학기 중간고사', 'date' => date('Y') . '-05-01'];
    } elseif (($month == 5 && $day >= 2) || $month == 6) {
        return ['type' => '1학기 기말고사', 'date' => date('Y') . '-07-01'];
    } elseif ($month >= 7 && $month <= 9) {
        return ['type' => '2학기 중간고사', 'date' => date('Y') . '-10-01'];
    } else {
        return ['type' => '2학기 기말고사', 'date' => date('Y') . '-12-10'];
    }
}

// Calculate D-Day
function getDdayText($examDate) {
    if (!$examDate) return 'D-Day';
    
    $today = new DateTime();
    $exam = new DateTime($examDate);
    $diff = $today->diff($exam);
    $days = (int)$diff->format('%R%a');
    
    if ($days > 0) return "D-{$days}";
    if ($days == 0) return 'D-Day';
    return 'D+' . abs($days);
}

// Get exam data from same school
function getSameSchoolExamData($school, $examType) {
    global $DB;
    
    try {
        // Convert exam type to Korean
        $examTypeKorean = [
            '1학기 중간고사' => '1학기 중간고사',
            '1학기 기말고사' => '1학기 기말고사',
            '2학기 중간고사' => '2학기 중간고사',
            '2학기 기말고사' => '2학기 기말고사'
        ][$examType] ?? $examType;
        
        $sql = "
            SELECT DISTINCT ed.start_date as exam_start_date, 
                   ed.end_date as exam_end_date, 
                   ed.math_date as math_exam_date,
                   er.tip_text as exam_scope
            FROM {alt42t_exams} e
            JOIN {alt42t_exam_dates} ed ON e.exam_id = ed.exam_id
            LEFT JOIN {alt42t_exam_resources} er ON e.exam_id = er.exam_id
            WHERE e.school_name = ? AND e.exam_type = ?
            ORDER BY e.timecreated DESC
            LIMIT 5
        ";
        
        $records = $DB->get_records_sql($sql, array($school, $examTypeKorean));
        
        $result = [];
        foreach ($records as $record) {
            $result[] = (array)$record;
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error fetching exam data: " . $e->getMessage());
        return [];
    }
}

// Save form data to alt42t tables
function saveFormData($data) {
    global $DB, $userid;
    
    try {
        // 1. Save/Update user info in alt42t_users
        $user_record = $DB->get_record('alt42t_users', array('userid' => $userid));
        
        if (!$user_record) {
            // Insert new user
            $user_data = new stdClass();
            $user_data->userid = $userid;
            $user_data->school_name = $data['school'];
            $user_data->grade = $data['grade'];
            $user_data->timecreated = time();
            $user_data->timemodified = time();
            $user_id = $DB->insert_record('alt42t_users', $user_data);
        } else {
            // Update existing user
            $user_record->school_name = $data['school'];
            $user_record->grade = $data['grade'];
            $user_record->timemodified = time();
            $DB->update_record('alt42t_users', $user_record);
            $user_id = $user_record->id;
        }
        
        // 2. Save exam info in alt42t_exams
        $exam_data = new stdClass();
        $exam_data->school_name = $data['school'];
        $exam_data->grade = $data['grade'];
        $exam_data->exam_type = $data['exam_type'];
        $exam_data->timecreated = time();
        $exam_id = $DB->insert_record('alt42t_exams', $exam_data);
        
        // 3. Save exam dates in alt42t_exam_dates
        $date_data = new stdClass();
        $date_data->exam_id = $exam_id;
        $date_data->user_id = $user_id;
        $date_data->start_date = $data['examStartDate'];
        $date_data->end_date = $data['examEndDate'];
        $date_data->math_date = $data['mathExamDate'];
        $date_data->status = $data['examStatus'] == 'confirmed' ? '확정' : '예상';
        $date_data->timecreated = time();
        $DB->insert_record('alt42t_exam_dates', $date_data);
        
        // 4. Save exam scope in alt42t_exam_resources
        if (!empty($data['examScope'])) {
            $resource_data = new stdClass();
            $resource_data->exam_id = $exam_id;
            $resource_data->user_id = $user_id;
            $resource_data->file_type = 'text';
            $resource_data->file_url = '';
            $resource_data->tip_text = '시험 범위: ' . $data['examScope'];
            $resource_data->upload_time = time();
            $DB->insert_record('alt42t_exam_resources', $resource_data);
        }
        
        // 5. Save study status in alt42t_study_status
        if (!empty($data['studyLevel'])) {
            $status_data = new stdClass();
            $status_data->user_id = $user_id;
            $status_data->exam_id = $exam_id;
            $status_data->status = $data['studyLevel'];
            $status_data->timecreated = time();
            $DB->insert_record('alt42t_study_status', $status_data);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving form data: " . $e->getMessage());
        return false;
    }
}

// Initialize form data from existing data
$birthYear = getUserBirthYear($lms_data);
$gradeCode = calculateGrade($birthYear);
$gradeFull = convertGradeFormat($gradeCode);

$formData = [
    'name' => $user->firstname . ' ' . $user->lastname,
    'school' => $lms_data['institute'] ?? $exam_info->school ?? '',
    'grade' => $gradeFull,
    'gradeCode' => $gradeCode,
    'semester' => calculateSemester(),
    'examType' => '',
    'examStartDate' => $exam_info->exam_start_date ?? '',
    'examEndDate' => $exam_info->exam_end_date ?? '',
    'mathExamDate' => $exam_info->math_exam_date ?? '',
    'examScope' => str_replace('시험 범위: ', '', $exam_info->exam_scope ?? ''),
    'examStatus' => $exam_info->exam_status ?? 'expected',
    'studyLevel' => $exam_info->study_status ?? ''
];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch($_POST['action']) {
        case 'updateFormData':
            // Session data update not needed with Moodle
            echo json_encode(['success' => true]);
            exit;
            
        case 'saveData':
            $data = json_decode($_POST['data'], true);
            $success = saveFormData($data);
            echo json_encode(['success' => $success]);
            exit;
            
        case 'getSameSchoolExams':
            $exams = getSameSchoolExamData($_POST['school'], $_POST['examType']);
            echo json_encode(['exams' => $exams]);
            exit;
    }
}

// Calculate exam type and date
$examInfo = calculateExamTypeAndDate();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학킹 시험 대비 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #E6F0FF 0%, #F0E6FF 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Header */
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

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #5B21B6;
        }

        .user-info {
            font-size: 0.875rem;
            color: #4B5563;
        }

        .progress-dots {
            display: flex;
            gap: 0.5rem;
        }

        .progress-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #D1D5DB;
            transition: background 0.3s;
        }

        .progress-dot.active {
            background: #5B21B6;
        }

        /* Container */
        .container {
            padding-top: 80px;
            min-height: 100vh;
        }

        .step-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            display: none;
        }

        .step-section.active {
            display: flex;
        }

        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            padding: 2rem;
            max-width: 512px;
            width: 100%;
        }

        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .icon {
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

        /* Form Elements */
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

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #5B21B6;
            box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
        }

        .btn-primary {
            background: #5B21B6;
            color: white;
        }

        .btn-primary:hover {
            background: #4C1D95;
        }

        .btn-secondary {
            background: #6B7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4B5563;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
        }

        /* Study Level Cards */
        .study-card {
            padding: 1.5rem;
            border: 2px solid #E5E7EB;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 1rem;
        }

        .study-card:hover {
            border-color: #5B21B6;
        }

        .study-card.selected {
            border-color: #5B21B6;
            background: #F3F0FF;
        }

        .study-card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .study-card-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }

        .study-card-content {
            flex: 1;
        }

        .study-card-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }

        .study-card-description {
            color: #6B7280;
            margin-bottom: 1rem;
        }

        .study-card-guide {
            background: #F3F4F6;
            border-radius: 0.5rem;
            padding: 0.75rem;
        }

        .study-card-guide-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .study-card-guide-text {
            font-size: 0.875rem;
            color: #6B7280;
        }

        /* Last Chunking Card */
        .strategy-card {
            background: linear-gradient(135deg, #FEF3C7 0%, #FED7AA 100%);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
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

        .strategy-text {
            color: #92400E;
            margin-bottom: 1.5rem;
        }

        .strategy-box {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .strategy-box-title {
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            font-size: 2rem;
            margin-bottom: 0.25rem;
        }

        .strategy-feature-text {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1F2937;
        }

        /* Exam Info Dropdown */
        .exam-info-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6B7280;
            background: none;
            border: none;
            cursor: pointer;
            margin-bottom: 1rem;
        }

        .exam-info-list {
            display: none;
        }

        .exam-info-list.show {
            display: block;
        }

        .exam-info-item {
            padding: 0.75rem;
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            cursor: pointer;
            margin-bottom: 0.5rem;
            transition: background 0.2s;
        }

        .exam-info-item:hover {
            background: #F9FAFB;
        }

        .exam-info-date {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .exam-info-details {
            font-size: 0.75rem;
            color: #6B7280;
        }

        /* Status Badge */
        .status-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .status-text {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        .status-badge.expected {
            background: #FEF3C7;
            color: #B45309;
        }

        .status-badge.confirmed {
            background: #D1FAE5;
            color: #047857;
        }

        .status-change {
            font-size: 0.875rem;
            color: #5B21B6;
            cursor: pointer;
            background: none;
            border: none;
        }

        .status-change:hover {
            color: #4C1D95;
        }

        /* Utility Classes */
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .mt-6 { margin-top: 1.5rem; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .hidden { display: none; }

        /* Responsive */
        @media (max-width: 640px) {
            .form-grid,
            .form-grid-3 {
                grid-template-columns: 1fr;
            }
            
            .strategy-features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="logo">수학킹</div>
                <div class="user-info" id="userInfo"></div>
            </div>
            <div class="progress-dots">
                <div class="progress-dot active"></div>
                <div class="progress-dot"></div>
                <div class="progress-dot"></div>
                <div class="progress-dot"></div>
                <div class="progress-dot"></div>
            </div>
        </div>
    </div>

    <!-- Container -->
    <div class="container">
        <!-- Step 1: 정보입력 -->
        <div class="step-section active" id="step1">
            <div class="card">
                <div class="card-header">
                    <div class="icon">🏫</div>
                    <h2 class="card-title">정보입력</h2>
                    <p class="card-subtitle">시험 대비를 위한 기본 정보를 입력해주세요</p>
                </div>

                <form id="infoForm">
                    <div class="form-group">
                        <label class="form-label">이름</label>
                        <input type="text" class="form-input" id="name" value="<?= htmlspecialchars($formData['name']) ?>" placeholder="이름을 입력하세요">
                    </div>

                    <div class="form-group">
                        <label class="form-label">학교</label>
                        <input type="text" class="form-input" id="school" value="<?= htmlspecialchars($formData['school']) ?>" placeholder="학교명을 입력하세요">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">학년</label>
                            <select class="form-select" id="grade">
                                <option value="">선택하세요</option>
                                <option value="중학교 1학년" <?= $formData['grade'] == '중학교 1학년' ? 'selected' : '' ?>>중학교 1학년</option>
                                <option value="중학교 2학년" <?= $formData['grade'] == '중학교 2학년' ? 'selected' : '' ?>>중학교 2학년</option>
                                <option value="중학교 3학년" <?= $formData['grade'] == '중학교 3학년' ? 'selected' : '' ?>>중학교 3학년</option>
                                <option value="고등학교 1학년" <?= $formData['grade'] == '고등학교 1학년' ? 'selected' : '' ?>>고등학교 1학년</option>
                                <option value="고등학교 2학년" <?= $formData['grade'] == '고등학교 2학년' ? 'selected' : '' ?>>고등학교 2학년</option>
                                <option value="고등학교 3학년" <?= $formData['grade'] == '고등학교 3학년' ? 'selected' : '' ?>>고등학교 3학년</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">학기</label>
                            <select class="form-select" id="semester">
                                <option value="1학기" <?= $formData['semester'] == '1학기' ? 'selected' : '' ?>>1학기</option>
                                <option value="2학기" <?= $formData['semester'] == '2학기' ? 'selected' : '' ?>>2학기</option>
                            </select>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" onclick="nextStep(1)">
                        다음으로
                        <span>→</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Step 2: 시험설정 -->
        <div class="step-section" id="step2">
            <div class="card">
                <div class="card-header">
                    <div class="icon">📅</div>
                    <h2 class="card-title">시험설정</h2>
                    <p class="card-subtitle">시험 일정과 범위를 설정해주세요</p>
                </div>

                <form id="examForm">
                    <div class="form-group">
                        <label class="form-label">시험 종류</label>
                        <select class="form-select" id="examType">
                            <option value="1학기 중간고사" <?= $examInfo['type'] == '1학기 중간고사' ? 'selected' : '' ?>>1학기 중간고사</option>
                            <option value="1학기 기말고사" <?= $examInfo['type'] == '1학기 기말고사' ? 'selected' : '' ?>>1학기 기말고사</option>
                            <option value="2학기 중간고사" <?= $examInfo['type'] == '2학기 중간고사' ? 'selected' : '' ?>>2학기 중간고사</option>
                            <option value="2학기 기말고사" <?= $examInfo['type'] == '2학기 기말고사' ? 'selected' : '' ?>>2학기 기말고사</option>
                        </select>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">시험 시작일</label>
                            <input type="date" class="form-input" id="examStartDate" value="<?= $examInfo['date'] ?>">
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

                    <div class="status-wrapper">
                        <span class="status-text">
                            현재 상태:
                            <span class="status-badge expected" id="statusBadge">예상</span>
                        </span>
                        <button type="button" class="status-change" onclick="toggleStatus()">상태 변경</button>
                    </div>

                    <div style="border-top: 1px solid #E5E7EB; padding-top: 1.5rem;">
                        <button type="button" class="exam-info-toggle" onclick="toggleExamInfo()">
                            <span>▼</span>
                            같은 학교 시험 정보 보기
                        </button>
                        <div class="exam-info-list" id="examInfoList"></div>
                    </div>

                    <div class="btn-group mt-4">
                        <button type="button" class="btn btn-secondary" onclick="openSchoolWebsite()">
                            🌐 학교 홈페이지
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                            다음으로
                            <span>→</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Step 3: 전략이해 -->
        <div class="step-section" id="step3">
            <div class="card">
                <div class="card-header">
                    <div class="icon">⚡</div>
                    <h2 class="card-title">전략이해</h2>
                    <p class="card-subtitle">라스트 청킹 전략을 알아보세요</p>
                </div>

                <div class="strategy-card">
                    <h3 class="strategy-title">
                        ⚡ 라스트 청킹
                    </h3>
                    <p class="strategy-subtitle">시험 직전 3-5일의 마법</p>
                    <p class="strategy-text">시험 직전 3-5일, 이게 진짜 게임 체인저야! 🎯</p>
                    
                    <div class="strategy-box">
                        <h4 class="strategy-box-title">
                            🧠 시험 3-5일 전 가벼운 복습
                        </h4>
                        <p style="color: #374151;">시험 전 마지막 5일, 성적을 폭발시키는 비밀 무기! 💥</p>
                    </div>

                    <div class="strategy-features">
                        <div class="strategy-feature">
                            <div class="strategy-feature-icon">🎯</div>
                            <div class="strategy-feature-text">컨디션 향상</div>
                        </div>
                        <div class="strategy-feature">
                            <div class="strategy-feature-icon">⚡</div>
                            <div class="strategy-feature-text">빠른 문제 해결</div>
                        </div>
                        <div class="strategy-feature">
                            <div class="strategy-feature-icon">🔥</div>
                            <div class="strategy-feature-text">기억력 강화</div>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" style="background: #EA580C;">
                        ⚡ 라스트청킹 시작하기
                    </button>
                    <button type="button" class="btn btn-primary" style="background: #D97706;" onclick="nextStep(3)">
                        다음으로
                        <span>→</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 4: 단계선택 -->
        <div class="step-section" id="step4">
            <div class="card">
                <div class="card-header">
                    <div class="icon">🎯</div>
                    <h2 class="card-title">단계선택</h2>
                    <p class="card-subtitle">너의 시작 위치는?</p>
                    <p class="mt-2" style="font-size: 0.875rem; color: #6B7280;">시작점을 선택해 주세요! 너에게 맞는 출발선을 찾아보자! 🚀</p>
                </div>

                <div id="studyLevelCards">
                    <div class="study-card" onclick="selectStudyLevel('concept')">
                        <div class="study-card-header">
                            <div class="study-card-icon">📚</div>
                            <div class="study-card-content">
                                <h3 class="study-card-title">개념공부</h3>
                                <p class="study-card-description">기본 개념부터 차근차근 시작해요</p>
                                <div class="study-card-guide">
                                    <p class="study-card-guide-title">📋 상세 가이드</p>
                                    <p class="study-card-guide-text">시험 기간이라면 완벽한 개념 학습보다는 핵심 개념을 빠르게 정리하는 것이 중요해요. 기본 개념만 확실히 잡고 문제 풀이로 넘어가세요.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="study-card" onclick="selectStudyLevel('review')">
                        <div class="study-card-header">
                            <div class="study-card-icon">🧠</div>
                            <div class="study-card-content">
                                <h3 class="study-card-title">개념복습</h3>
                                <p class="study-card-description">배운 개념들을 다시 정리하고 복습해요</p>
                                <div class="study-card-guide">
                                    <p class="study-card-guide-title">📋 상세 가이드</p>
                                    <p class="study-card-guide-text">이미 배운 개념들을 빠르게 복습하는 단계예요. • 기억이 안 나면: 개념 다시 정리 • 어느 정도 기억나면: 유형 테스트 3회 → 단원 테스트 90점 • 개념이 잡혀있으면: 바로 단원 테스트 90점 도전</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="study-card" onclick="selectStudyLevel('practice')">
                        <div class="study-card-header">
                            <div class="study-card-icon">✏️</div>
                            <div class="study-card-content">
                                <h3 class="study-card-title">유형공부</h3>
                                <p class="study-card-description">다양한 문제 유형들을 학습해요</p>
                                <div class="study-card-guide">
                                    <p class="study-card-guide-title">📋 상세 가이드</p>
                                    <p class="study-card-guide-text">mathking 내신테스트로 시작해서 중급→심화 유형 순서로 학습하세요. 교재 문제와 병행하며 마지막에 기출문제까지 완주!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-primary mt-6" id="studyNextBtn" style="background: #7C3AED;" onclick="nextStep(4)" disabled>
                    다음으로
                    <span>→</span>
                </button>
            </div>
        </div>

        <!-- Step 5: 시작하기 -->
        <div class="step-section" id="step5">
            <div class="card">
                <div class="text-center">
                    <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #5B21B6 0%, #7C3AED 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <span style="font-size: 2rem; color: white;">🚀</span>
                    </div>
                    <h2 class="card-title">시작하기</h2>
                    <p class="card-subtitle">모든 설정이 완료되었습니다!</p>
                    
                    <button type="button" class="btn btn-primary mt-6" onclick="startDashboard()">
                        대시보드로 이동
                        <span>→</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let formData = <?= json_encode($formData) ?>;
        let examStatus = 'expected';
        let selectedStudyLevel = '';

        // Update header info
        function updateHeaderInfo() {
            const userInfo = document.getElementById('userInfo');
            if (formData.name && formData.mathExamDate) {
                const dday = getDdayText(formData.mathExamDate);
                userInfo.textContent = `${formData.name} | ${formData.school} | ${formData.grade} | ${formData.examType} | 수학 시험 ${dday}`;
            }
        }

        // Calculate D-Day
        function getDdayText(examDate) {
            if (!examDate) return 'D-Day';
            
            const today = new Date();
            const exam = new Date(examDate);
            const diffTime = exam - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays > 0) return `D-${diffDays}`;
            if (diffDays === 0) return 'D-Day';
            return `D+${Math.abs(diffDays)}`;
        }

        // Update progress dots
        function updateProgress(step) {
            const dots = document.querySelectorAll('.progress-dot');
            dots.forEach((dot, index) => {
                if (index < step) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }

        // Save form data to session via AJAX
        async function saveFormData() {
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=updateFormData&data=${encodeURIComponent(JSON.stringify(formData))}`
            });
            return response.json();
        }

        // Move to next step
        async function nextStep(step) {
            // Validate current step
            if (!validateStep(step)) return;

            // Save current step data
            updateFormDataFromStep(step);
            await saveFormData();

            // Hide current step
            document.getElementById(`step${step}`).classList.remove('active');
            
            // Show next step
            currentStep = step + 1;
            document.getElementById(`step${currentStep}`).classList.add('active');
            
            // Update progress
            updateProgress(currentStep);
            
            // Update header info
            updateHeaderInfo();

            // Special handling for step 2
            if (currentStep === 2) {
                loadSameSchoolExams();
            }

            // Save to database on step 2
            if (step === 2) {
                await saveToDatabase();
            }
        }

        // Validate step
        function validateStep(step) {
            switch(step) {
                case 1:
                    const name = document.getElementById('name').value;
                    const school = document.getElementById('school').value;
                    const grade = document.getElementById('grade').value;
                    
                    if (!name || !school || !grade) {
                        alert('모든 필수 정보를 입력해주세요.');
                        return false;
                    }
                    return true;
                    
                case 2:
                    const examStartDate = document.getElementById('examStartDate').value;
                    const mathExamDate = document.getElementById('mathExamDate').value;
                    
                    if (!examStartDate || !mathExamDate) {
                        alert('시험 시작일과 수학 시험일을 입력해주세요.');
                        return false;
                    }
                    return true;
                    
                case 4:
                    if (!selectedStudyLevel) {
                        alert('학습 단계를 선택해주세요.');
                        return false;
                    }
                    return true;
                    
                default:
                    return true;
            }
        }

        // Update form data from current step
        function updateFormDataFromStep(step) {
            switch(step) {
                case 1:
                    formData.name = document.getElementById('name').value;
                    formData.school = document.getElementById('school').value;
                    formData.grade = document.getElementById('grade').value;
                    formData.semester = document.getElementById('semester').value;
                    break;
                    
                case 2:
                    formData.examType = document.getElementById('examType').value;
                    formData.examStartDate = document.getElementById('examStartDate').value;
                    formData.examEndDate = document.getElementById('examEndDate').value;
                    formData.mathExamDate = document.getElementById('mathExamDate').value;
                    formData.examScope = document.getElementById('examScope').value;
                    formData.examStatus = examStatus;
                    break;
                    
                case 4:
                    formData.studyLevel = selectedStudyLevel;
                    break;
            }
        }

        // Toggle exam status
        function toggleStatus() {
            examStatus = examStatus === 'expected' ? 'confirmed' : 'expected';
            const badge = document.getElementById('statusBadge');
            badge.textContent = examStatus === 'expected' ? '예상' : '확정';
            badge.className = `status-badge ${examStatus}`;
        }

        // Toggle exam info
        function toggleExamInfo() {
            const examList = document.getElementById('examInfoList');
            examList.classList.toggle('show');
        }

        // Load same school exam data
        async function loadSameSchoolExams() {
            const school = formData.school;
            const examType = document.getElementById('examType').value;
            
            if (!school || !examType) return;
            
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=getSameSchoolExams&school=${encodeURIComponent(school)}&examType=${encodeURIComponent(examType)}`
            });
            
            const data = await response.json();
            displayExamList(data.exams);
        }

        // Display exam list
        function displayExamList(exams) {
            const examList = document.getElementById('examInfoList');
            examList.innerHTML = '';
            
            if (exams.length === 0) {
                examList.innerHTML = '<p style="color: #6B7280; font-size: 0.875rem;">같은 학교의 시험 정보가 없습니다.</p>';
                return;
            }
            
            exams.forEach(exam => {
                const item = document.createElement('div');
                item.className = 'exam-info-item';
                item.onclick = () => selectExamData(exam);
                item.innerHTML = `
                    <div class="exam-info-date">${exam.exam_start_date} ~ ${exam.exam_end_date}</div>
                    <div class="exam-info-details">수학: ${exam.math_exam_date} | 범위: ${exam.exam_scope}</div>
                `;
                examList.appendChild(item);
            });
        }

        // Select exam data
        function selectExamData(exam) {
            document.getElementById('examStartDate').value = exam.exam_start_date;
            document.getElementById('examEndDate').value = exam.exam_end_date;
            document.getElementById('mathExamDate').value = exam.math_exam_date;
            document.getElementById('examScope').value = exam.exam_scope;
            
            // Update form data
            formData.examStartDate = exam.exam_start_date;
            formData.examEndDate = exam.exam_end_date;
            formData.mathExamDate = exam.math_exam_date;
            formData.examScope = exam.exam_scope;
            
            // Hide exam list
            document.getElementById('examInfoList').classList.remove('show');
        }

        // Open school website
        function openSchoolWebsite() {
            const school = formData.school;
            if (school) {
                window.open(`https://www.google.com/search?q=${encodeURIComponent(school)} 홈페이지`, '_blank');
            }
        }

        // Select study level
        function selectStudyLevel(level) {
            selectedStudyLevel = level;
            
            // Update UI
            const cards = document.querySelectorAll('.study-card');
            cards.forEach(card => card.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            
            // Enable next button
            document.getElementById('studyNextBtn').disabled = false;
        }

        // Save to database
        async function saveToDatabase() {
            // Add grade code to form data
            formData.gradeCode = '<?= $gradeCode ?>';
            
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=saveData&data=${encodeURIComponent(JSON.stringify(formData))}`
            });
            const result = await response.json();
            if (!result.success) {
                console.error('Failed to save data to database');
            }
        }

        // Start dashboard
        function startDashboard() {
            // Save final data
            saveToDatabase().then(() => {
                // Redirect to dashboard
                window.location.href = 'dashboard.php?userid=<?= $userid ?>';
            });
        }

        // Initialize
        updateHeaderInfo();
    </script>
</body>
</html>