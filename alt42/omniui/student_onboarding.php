<?php
// 세션 시작
session_start();

// 데이터베이스 연결
require_once 'config.php';

// 로그인/사용자 식별: GET 파라미터(userid)가 있으면 우선 적용하여 세션에 설정
$requested_user_id = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
if ($requested_user_id > 0) {
    $_SESSION['user_id'] = $requested_user_id;
}

// 로그인 체크 (세션 또는 위의 userid 설정)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);

// 사용자 정보를 DB에서 가져오기
try {
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 사용자 기본 정보 조회
    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone1, u.phone2
            FROM mdl_user u
            WHERE u.id = ? AND u.deleted = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // 사용자 추가 정보 조회 (institute, phone2, phone3)
    $user_info_data = [];
    $info_sql = "SELECT fieldid, data FROM mdl_user_info_data WHERE userid = ?";
    $info_stmt = $pdo->prepare($info_sql);
    $info_stmt->execute([$user_id]);
    while ($row = $info_stmt->fetch()) {
        $user_info_data[$row['fieldid']] = $row['data'];
    }

    // 데이터 매핑
    $school_name = isset($user_info_data[88]) ? $user_info_data[88] : ''; // institute
    $student_name = trim($user['firstname'] . ' ' . $user['lastname']);
    $student_phone = !empty($user_info_data[54]) ? $user_info_data[54] : (!empty($user['phone1']) ? $user['phone1'] : '010-');
    $parent_phone_father = isset($user_info_data[85]) ? $user_info_data[85] : '010-'; // phone2
    $parent_phone_mother = isset($user_info_data[55]) ? $user_info_data[55] : '010-'; // phone3

    // 학교급 판별 (초/중/고)
    $course_level = '중등'; // 기본값
    $course_level_from_school = ''; // 학교명으로 판별한 학교급

    if (!empty($school_name)) {
        // 마지막 문자 확인
        $last_char = mb_substr($school_name, -1);
        $last_two_chars = mb_substr($school_name, -2);
        $last_three_chars = mb_substr($school_name, -3);
        $last_four_chars = mb_substr($school_name, -4);

        // 고등학교 판별 (고로 끝나는 경우 포함)
        if ($last_char === '고' ||
            $last_two_chars === '고교' ||
            $last_two_chars === '고등' ||
            $last_three_chars === '고등학' ||
            $last_four_chars === '고등학교') {
            $course_level_from_school = '고등';
        }
        // 중학교 판별 (중으로 끝나는 경우 포함)
        elseif ($last_char === '중' ||
                $last_two_chars === '중교' ||
                $last_two_chars === '중학' ||
                $last_three_chars === '중학교') {
            $course_level_from_school = '중등';
        }
        // 초등학교 판별 (초로 끝나는 경우 포함)
        elseif ($last_char === '초' ||
                $last_two_chars === '초교' ||
                $last_two_chars === '초등' ||
                $last_three_chars === '초등학' ||
                $last_four_chars === '초등학교') {
            $course_level_from_school = '초등';
        }
        // 추가 패턴 확인
        elseif (strpos($school_name, '고등') !== false || strpos($school_name, '고교') !== false) {
            $course_level_from_school = '고등';
        }
        elseif (strpos($school_name, '중학') !== false || strpos($school_name, '중교') !== false) {
            $course_level_from_school = '중등';
        }
        elseif (strpos($school_name, '초등') !== false || strpos($school_name, '초교') !== false) {
            $course_level_from_school = '초등';
        }

        // 학교명으로 판별된 값이 있으면 우선 사용
        if (!empty($course_level_from_school)) {
            $course_level = $course_level_from_school;
        }
    }

    // 출생년도로 학년 판별
    $current_year = date('Y');
    $current_month = intval(date('n')); // 현재 월
    $birth_year = 0;
    $grade_detail = '';

    // fieldid 89에서 출생년도 가져오기 (birthdate)
    if (isset($user_info_data[89]) && !empty($user_info_data[89])) {
        // 날짜 형식 처리 (YYYY-MM-DD, YYYY/MM/DD, YYYYMMDD, YYYY 등)
        $birthdate_str = trim($user_info_data[89]);

        // 다양한 날짜 형식 처리
        // 1. 4자리 년도로 시작하는 경우 (YYYY-MM-DD, YYYY/MM/DD, YYYY.MM.DD, YYYYMMDD 등)
        if (preg_match('/^(\d{4})/', $birthdate_str, $matches)) {
            $birth_year = intval($matches[1]);
        }
        // 2. DD-MM-YYYY, DD/MM/YYYY, DD.MM.YYYY 형식
        elseif (preg_match('/^\d{1,2}[-\/\.]\d{1,2}[-\/\.](\d{4})/', $birthdate_str, $matches)) {
            $birth_year = intval($matches[1]);
        }
        // 3. YY-MM-DD, YY/MM/DD 형식 (2자리 연도)
        elseif (preg_match('/^(\d{2})[-\/\.]/', $birthdate_str, $matches)) {
            $year_suffix = intval($matches[1]);
            // 50-99는 1950-1999, 00-49는 2000-2049로 처리
            if ($year_suffix >= 50) {
                $birth_year = 1900 + $year_suffix;
            } else {
                $birth_year = 2000 + $year_suffix;
            }
        }
        // 4. 단순 숫자만 있는 경우 (6자리: YYMMDD, 8자리: YYYYMMDD)
        elseif (preg_match('/^\d+$/', $birthdate_str)) {
            if (strlen($birthdate_str) == 8) {
                // YYYYMMDD
                $birth_year = intval(substr($birthdate_str, 0, 4));
            } elseif (strlen($birthdate_str) == 6) {
                // YYMMDD
                $year_suffix = intval(substr($birthdate_str, 0, 2));
                if ($year_suffix >= 50) {
                    $birth_year = 1900 + $year_suffix;
                } else {
                    $birth_year = 2000 + $year_suffix;
                }
            }
        }
    }

    // birthdate 필드가 없으면 이름에서 출생년도 추출 시도
    if ($birth_year == 0 && preg_match('/(\d{2})$/', $user['lastname'], $matches)) {
        $year_suffix = intval($matches[1]);
        // 00-24는 2000년대, 95-99는 1900년대로 가정
        if ($year_suffix <= 24) {
            $birth_year = 2000 + $year_suffix;
        } else {
            $birth_year = 1900 + $year_suffix;
        }
    }

    // 한국 학제 기준 학년 계산 (2024년 기준)
    // 3월 이후는 신학기, 1-2월은 이전 학년
    if ($birth_year > 0) {
        $school_year = $current_year;
        if ($current_month < 3) {
            $school_year = $current_year - 1; // 1-2월은 이전 학년
        }

        // 학년 계산 (학교급에 관계없이 먼저 전체 학년 계산)
        // 한국은 보통 8살(만 7세)에 초등학교 입학
        $grade = $school_year - $birth_year - 6;  // 정확한 학년 계산

        // 초등학교 (1-6학년)
        if ($grade >= 1 && $grade <= 6) {
            $course_level = '초등';
            if ($grade >= 4 && $grade <= 6) {
                $grade_detail = ($grade - 3) . '학년'; // 4, 5, 6학년
            } else {
                $grade_detail = $grade . '학년'; // 1, 2, 3학년
            }
        }
        // 중학교 (7-9학년 -> 중1-3)
        elseif ($grade >= 7 && $grade <= 9) {
            $course_level = '중등';
            $grade_detail = ($grade - 6) . '학년';
        }
        // 고등학교 (10-12학년 -> 고1-3)
        elseif ($grade >= 10 && $grade <= 12) {
            $course_level = '고등';
            $grade_detail = ($grade - 9) . '학년';
        }

        // 구체적인 출생년도별 학년 (2024년 기준)
        if ($school_year == 2024) {
            switch($birth_year) {
                case 2018: $course_level = '초등'; $grade_detail = '1학년'; break;
                case 2017: $course_level = '초등'; $grade_detail = '2학년'; break;
                case 2016: $course_level = '초등'; $grade_detail = '3학년'; break;
                case 2015: $course_level = '초등'; $grade_detail = '4학년'; break;
                case 2014: $course_level = '초등'; $grade_detail = '5학년'; break;
                case 2013: $course_level = '초등'; $grade_detail = '6학년'; break;
                case 2012: $course_level = '중등'; $grade_detail = '1학년'; break;
                case 2011: $course_level = '중등'; $grade_detail = '2학년'; break;
                case 2010: $course_level = '중등'; $grade_detail = '3학년'; break;
                case 2009: $course_level = '고등'; $grade_detail = '1학년'; break;
                case 2008: $course_level = '고등'; $grade_detail = '2학년'; break;
                case 2007: $course_level = '고등'; $grade_detail = '3학년'; break;
                case 2006: $course_level = '고등'; $grade_detail = '3학년'; break; // 재수생
            }
        }
    }

    // 학년이 자동 판별되지 않았으면 기본값 설정
    if (empty($grade_detail)) {
        if ($course_level === '초등') {
            $grade_detail = '5학년'; // 기본값
        } elseif ($course_level === '중등') {
            $grade_detail = '2학년'; // 기본값
        } elseif ($course_level === '고등') {
            $grade_detail = '1학년'; // 기본값
        }
    }

    // 저장된 학습 데이터 가져오기
    $learning_progress = null;
    $learning_style = null;
    $learning_method = null;
    $learning_goals = null;
    $additional_info = null;

    try {
        // 학습 진도 데이터 조회
        $progress_sql = "SELECT * FROM mdl_alt42g_learning_progress WHERE userid = ?";
        $progress_stmt = $pdo->prepare($progress_sql);
        $progress_stmt->execute([$user_id]);
        $learning_progress = $progress_stmt->fetch();

        // 학습 스타일 데이터 조회
        $style_sql = "SELECT * FROM mdl_alt42g_learning_style WHERE userid = ?";
        $style_stmt = $pdo->prepare($style_sql);
        $style_stmt->execute([$user_id]);
        $learning_style = $style_stmt->fetch();

        // 학습 방식 데이터 조회
        $method_sql = "SELECT * FROM mdl_alt42g_learning_method WHERE userid = ?";
        $method_stmt = $pdo->prepare($method_sql);
        $method_stmt->execute([$user_id]);
        $learning_method = $method_stmt->fetch();

        // 학습 목표 데이터 조회
        $goals_sql = "SELECT * FROM mdl_alt42g_learning_goals WHERE userid = ?";
        $goals_stmt = $pdo->prepare($goals_sql);
        $goals_stmt->execute([$user_id]);
        $learning_goals = $goals_stmt->fetch();

        // 추가 정보 데이터 조회
        $additional_sql = "SELECT * FROM mdl_alt42g_additional_info WHERE userid = ?";
        $additional_stmt = $pdo->prepare($additional_sql);
        $additional_stmt->execute([$user_id]);
        $additional_info = $additional_stmt->fetch();

    } catch (PDOException $e) {
        error_log("Error loading learning data: " . $e->getMessage());
        // 에러 발생 시 null 값 유지
    }

    // 전화번호 포맷 정리
    if (!empty($student_phone) && !preg_match('/^010-/', $student_phone)) {
        $student_phone = '010-' . preg_replace('/[^0-9]/', '', $student_phone);
    }
    if (empty($student_phone)) {
        $student_phone = '010-';
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // 에러 발생시 기본값 사용
    $school_name = '';
    $student_name = '';
    $student_phone = '010-';
    $parent_phone_father = '010-';
    $parent_phone_mother = '010-';
    $course_level = '중등';
    $grade_detail = '';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>카이스트 터치수학 학원 - 학생 온보딩</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fadeIn': 'fadeIn 0.5s ease-in-out',
                        'pulse': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite'
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-in-out;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        /* 세로 텍스트 스타일 */
        .vertical-text {
            writing-mode: vertical-lr;
            text-orientation: upright;
            font-size: 12px;
            letter-spacing: -1px;
        }

        /* 슬라이더 커스터마이징 */
        input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
            cursor: pointer;
        }

        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
            transition: all 0.2s ease;
        }

        input[type="range"]::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.6);
        }

        input[type="range"]::-webkit-slider-thumb:active {
            transform: scale(1.1);
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
        }

        input[type="range"]::-moz-range-thumb {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
            transition: all 0.2s ease;
        }

        input[type="range"]::-moz-range-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.6);
        }

        input[type="range"]::-moz-range-thumb:active {
            transform: scale(1.1);
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
        }

        /* 버튼 클릭 효과 */
        .btn-click {
            transition: all 0.1s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-click:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }

        .btn-click:active {
            transform: translateY(1px) scale(0.95);
            box-shadow: 0 2px 5px rgba(139, 92, 246, 0.5);
        }

        /* 즉각적인 클릭 리플 효과 */
        @keyframes ripple {
            0% {
                transform: translate(-50%, -50%) scale(0);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(4);
                opacity: 0;
            }
        }

        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            width: 50px;
            height: 50px;
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        }

        /* 선택된 버튼 펄스 효과 */
        @keyframes pulse-border {
            0% {
                box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(139, 92, 246, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(139, 92, 246, 0);
            }
        }

        .selected-pulse {
            animation: pulse-border 2s infinite;
        }

        /* 즉각적인 플래시 효과 */
        @keyframes flash {
            0% {
                background-color: rgba(139, 92, 246, 0.3);
            }
            100% {
                background-color: transparent;
            }
        }

        .flash-effect {
            animation: flash 0.3s ease-out;
        }

        /* 탭 전환 애니메이션 */
        .tab-content {
            animation: fadeIn 0.5s ease-in-out;
        }

        .typing-cursor {
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">
                카이스트 터치수학 학원
            </h1>
            <p class="text-gray-600">맞춤형 수학 교육을 위한 온보딩 시스템</p>
        </div>

        <!-- View Mode Toggle -->
        <div class="flex justify-center mb-6">
            <div class="bg-white rounded-full shadow-lg p-1 flex">
                <button onclick="setViewMode('tab')" id="tabViewBtn" class="px-6 py-2 rounded-full font-medium transition-all flex items-center text-gray-600 hover:text-gray-800">
                    <i data-lucide="layers" class="mr-2 w-4 h-4"></i> 탭 뷰
                </button>
                <button onclick="setViewMode('scroll')" id="scrollViewBtn" class="px-6 py-2 rounded-full font-medium transition-all flex items-center bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg">
                    <i data-lucide="scroll-text" class="mr-2 w-4 h-4"></i> 스크롤 뷰
                </button>
            </div>
        </div>

        <!-- Form Container -->
        <form id="onboardingForm" method="POST" action="save_onboarding.php">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

            <!-- Hidden inputs for form data -->
            <input type="hidden" name="school" id="school_hidden">
            <input type="hidden" name="studentName" id="studentName_hidden">
            <input type="hidden" name="studentPhone" id="studentPhone_hidden">
            <input type="hidden" name="parentPhoneFather" id="parentPhoneFather_hidden">
            <input type="hidden" name="parentPhoneMother" id="parentPhoneMother_hidden">
            <input type="hidden" name="address" id="address_hidden">
            <input type="hidden" name="courseLevel" id="courseLevel_hidden">
            <input type="hidden" name="gradeDetail" id="gradeDetail_hidden">
            <input type="hidden" name="mathLevel" id="mathLevel_hidden">
            <input type="hidden" name="conceptLevel" id="conceptLevel_hidden">
            <input type="hidden" name="conceptProgress" id="conceptProgress_hidden">
            <input type="hidden" name="advancedLevel" id="advancedLevel_hidden">
            <input type="hidden" name="advancedProgress" id="advancedProgress_hidden">
            <input type="hidden" name="notes" id="notes_hidden">
            <input type="hidden" name="problemPreference" id="problemPreference_hidden">
            <input type="hidden" name="examStyle" id="examStyle_hidden">
            <input type="hidden" name="mathConfidence" id="mathConfidence_hidden">
            <input type="hidden" name="parentStyle" id="parentStyle_hidden">
            <input type="hidden" name="stressLevel" id="stressLevel_hidden">
            <input type="hidden" name="feedbackPreference" id="feedbackPreference_hidden">
            <input type="hidden" name="shortTermGoal" id="shortTermGoal_hidden">
            <input type="hidden" name="midTermGoal" id="midTermGoal_hidden">
            <input type="hidden" name="longTermGoal" id="longTermGoal_hidden">
            <input type="hidden" name="goalNote" id="goalNote_hidden">
            <input type="hidden" name="weeklyHours" id="weeklyHours_hidden">
            <input type="hidden" name="academyExperience" id="academyExperience_hidden">
            <input type="hidden" name="dataConsent" id="dataConsent_hidden">
            <input type="hidden" name="favoriteFood" id="favoriteFood_hidden">
            <input type="hidden" name="favoriteFruit" id="favoriteFruit_hidden">
            <input type="hidden" name="favoriteSnack" id="favoriteSnack_hidden">
            <input type="hidden" name="hobbiesInterests" id="hobbiesInterests_hidden">
            <input type="hidden" name="fandomYN" id="fandomYN_hidden">

            <!-- Tab View Container -->
            <div id="tabView" class="hidden">
                <!-- Tab Navigation -->
                <div class="bg-white rounded-2xl shadow-lg p-2 mb-6">
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                        <button type="button" onclick="goToTab(0)" class="tab-btn px-4 py-3 rounded-xl font-medium transition-all flex flex-col items-center bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg transform scale-105">
                            <i data-lucide="user" class="w-5 h-5 mb-1"></i>
                            <span class="text-xs">기본 정보</span>
                        </button>
                        <button type="button" onclick="goToTab(1)" class="tab-btn px-4 py-3 rounded-xl font-medium transition-all flex flex-col items-center text-gray-600 hover:bg-gray-100">
                            <i data-lucide="book-open" class="w-5 h-5 mb-1"></i>
                            <span class="text-xs">학습 진도</span>
                        </button>
                        <button type="button" onclick="goToTab(2)" class="tab-btn px-4 py-3 rounded-xl font-medium transition-all flex flex-col items-center text-gray-600 hover:bg-gray-100">
                            <i data-lucide="brain" class="w-5 h-5 mb-1"></i>
                            <span class="text-xs">학습 스타일</span>
                        </button>
                        <button type="button" onclick="goToTab(3)" class="tab-btn px-4 py-3 rounded-xl font-medium transition-all flex flex-col items-center text-gray-600 hover:bg-gray-100">
                            <i data-lucide="settings" class="w-5 h-5 mb-1"></i>
                            <span class="text-xs">학습 방식</span>
                        </button>
                        <button type="button" onclick="goToTab(4)" class="tab-btn px-4 py-3 rounded-xl font-medium transition-all flex flex-col items-center text-gray-600 hover:bg-gray-100">
                            <i data-lucide="target" class="w-5 h-5 mb-1"></i>
                            <span class="text-xs">목표 설정</span>
                        </button>
                        <button type="button" onclick="goToTab(5)" class="tab-btn px-4 py-3 rounded-xl font-medium transition-all flex flex-col items-center text-gray-600 hover:bg-gray-100">
                            <i data-lucide="heart" class="w-5 h-5 mb-1"></i>
                            <span class="text-xs">추가 정보</span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
                    <div id="tabContent"></div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between">
                    <button type="button" onclick="previousTab()" id="prevBtn" class="px-6 py-3 bg-gray-200 text-gray-400 rounded-lg font-medium transition-all flex items-center cursor-not-allowed">
                        <i data-lucide="chevron-left" class="mr-2 w-5 h-5"></i> 이전
                    </button>
                    <button type="button" onclick="nextTab()" id="nextBtn" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all flex items-center">
                        다음 <i data-lucide="chevron-right" class="ml-2 w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Scroll View Container -->
            <div id="scrollView" class="space-y-8">
                <!-- 기본 정보 섹션 -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i data-lucide="user" class="mr-2 text-purple-500 w-6 h-6"></i> 기본 정보
                    </h3>
                    <div id="basicInfoContent"></div>
                </div>

                <!-- 학습 진도 섹션 -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i data-lucide="book-open" class="mr-2 text-blue-500 w-6 h-6"></i> 학습 진도
                    </h3>
                    <div id="learningProgressContent"></div>
                </div>

                <!-- 학습 스타일 섹션 -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i data-lucide="brain" class="mr-2 text-green-500 w-6 h-6"></i> 학습 스타일
                    </h3>
                    <div id="learningStyleContent"></div>
                </div>

                <!-- 학습 방식 섹션 -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i data-lucide="settings" class="mr-2 text-indigo-500 w-6 h-6"></i> 학습 방식
                    </h3>
                    <div id="learningMethodContent"></div>
                </div>

                <!-- 목표 설정 섹션 -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i data-lucide="target" class="mr-2 text-orange-500 w-6 h-6"></i> 목표 설정
                    </h3>
                    <div id="goalSettingContent"></div>
                </div>

                <!-- 추가 정보 섹션 -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i data-lucide="heart" class="mr-2 text-pink-500 w-6 h-6"></i> 추가 정보
                    </h3>
                    <div id="additionalInfoContent"></div>
                </div>

                <!-- Submit Button -->
                <div class="mt-8 text-center">
                    <button type="submit" class="px-8 py-4 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-bold shadow-xl hover:shadow-2xl transition-all transform hover:scale-105 flex items-center mx-auto">
                        <i data-lucide="check-circle" class="mr-2 w-6 h-6"></i> 온보딩 완료
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // 전역 변수
        let viewMode = 'scroll';
        let activeTab = 0;
        let formData = {
            school: '<?php echo addslashes($school_name); ?>',
            studentName: '<?php echo addslashes($student_name); ?>',
            studentPhone: '<?php echo addslashes($student_phone); ?>',
            parentPhoneFather: '<?php echo addslashes($parent_phone_father); ?>',
            parentPhoneMother: '<?php echo addslashes($parent_phone_mother); ?>',
            address: '',
            courseLevel: '<?php echo $course_level; ?>',
            gradeDetail: '<?php echo $grade_detail; ?>',
            mathLevel: '<?php echo isset($learning_progress['math_level']) ? addslashes($learning_progress['math_level']) : ''; ?>',
            conceptLevel: '<?php echo isset($learning_progress['concept_level']) ? addslashes($learning_progress['concept_level']) : '중등'; ?>',
            conceptProgress: <?php echo isset($learning_progress['concept_progress']) ? $learning_progress['concept_progress'] : 1; ?>,
            advancedLevel: '<?php echo isset($learning_progress['advanced_level']) ? addslashes($learning_progress['advanced_level']) : '중등'; ?>',
            advancedProgress: <?php echo isset($learning_progress['advanced_progress']) ? $learning_progress['advanced_progress'] : 1; ?>,
            notes: '<?php echo isset($learning_progress['notes']) ? addslashes($learning_progress['notes']) : ''; ?>',
            problemPreference: '<?php echo isset($learning_style['problem_preference']) ? addslashes($learning_style['problem_preference']) : ''; ?>',
            examStyle: '<?php echo isset($learning_style['exam_style']) ? addslashes($learning_style['exam_style']) : ''; ?>',
            mathConfidence: <?php echo isset($learning_style['math_confidence']) ? $learning_style['math_confidence'] : 5; ?>,
            parentStyle: '<?php echo isset($learning_method['parent_style']) ? addslashes($learning_method['parent_style']) : ''; ?>',
            stressLevel: '<?php echo isset($learning_method['stress_level']) ? addslashes($learning_method['stress_level']) : ''; ?>',
            feedbackPreference: '<?php echo isset($learning_method['feedback_preference']) ? addslashes($learning_method['feedback_preference']) : ''; ?>',
            shortTermGoal: '<?php echo isset($learning_goals['short_term_goal']) ? addslashes($learning_goals['short_term_goal']) : ''; ?>',
            midTermGoal: '<?php echo isset($learning_goals['mid_term_goal']) ? addslashes($learning_goals['mid_term_goal']) : ''; ?>',
            longTermGoal: '<?php echo isset($learning_goals['long_term_goal']) ? addslashes($learning_goals['long_term_goal']) : ''; ?>',
            goalNote: '<?php echo isset($learning_goals['goal_note']) ? addslashes($learning_goals['goal_note']) : ''; ?>',
            weeklyHours: <?php echo isset($additional_info['weekly_hours']) ? $additional_info['weekly_hours'] : (isset($learning_progress['weekly_hours']) ? $learning_progress['weekly_hours'] : 10); ?>,
            academyExperience: '<?php echo isset($additional_info['academy_experience']) ? addslashes($additional_info['academy_experience']) : (isset($learning_progress['academy_experience']) ? addslashes($learning_progress['academy_experience']) : ''); ?>',
            dataConsent: <?php echo isset($additional_info['data_consent']) && $additional_info['data_consent'] ? 'true' : 'false'; ?>,
            favoriteFood: '<?php echo isset($additional_info['favorite_food']) ? addslashes($additional_info['favorite_food']) : ''; ?>',
            favoriteFruit: '<?php echo isset($additional_info['favorite_fruit']) ? addslashes($additional_info['favorite_fruit']) : ''; ?>',
            favoriteSnack: '<?php echo isset($additional_info['favorite_snack']) ? addslashes($additional_info['favorite_snack']) : ''; ?>',
            hobbiesInterests: '<?php echo isset($additional_info['hobbies_interests']) ? addslashes($additional_info['hobbies_interests']) : ''; ?>',
            fandomYN: <?php echo isset($additional_info['fandom_yn']) && $additional_info['fandom_yn'] ? 'true' : 'false'; ?>
        };

        // 진도 옵션
        const progressOptions = {
            '초등': ['초등4-1', '초등4-2', '초등5-1', '초등5-2', '초등6-1', '초등6-2'],
            '중등': ['중등1-1', '중등1-2', '중등2-1', '중등2-2', '중등3-1', '중등3-2'],
            '고등': ['공통수학1', '공통수학2', '대수', '미적분1', '확률과통계', '미적분2', '기하']
        };

        const gradeOptions = {
            '초등': ['4학년', '5학년', '6학년'],
            '중등': ['1학년', '2학년', '3학년'],
            '고등': ['1학년', '2학년', '3학년']
        };

        // 현재 뷰 모드에 맞게 렌더링
        function renderCurrentView() {
            if (viewMode === 'tab') {
                renderTabContent(activeTab);
            } else {
                renderAllContent();
            }
        }

        // 뷰 모드 전환
        function setViewMode(mode) {
            viewMode = mode;
            if (mode === 'tab') {
                document.getElementById('tabView').classList.remove('hidden');
                document.getElementById('scrollView').classList.add('hidden');
                document.getElementById('tabViewBtn').className = 'px-6 py-2 rounded-full font-medium transition-all flex items-center bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg';
                document.getElementById('scrollViewBtn').className = 'px-6 py-2 rounded-full font-medium transition-all flex items-center text-gray-600 hover:text-gray-800';
                renderTabContent(activeTab);
            } else {
                document.getElementById('tabView').classList.add('hidden');
                document.getElementById('scrollView').classList.remove('hidden');
                document.getElementById('tabViewBtn').className = 'px-6 py-2 rounded-full font-medium transition-all flex items-center text-gray-600 hover:text-gray-800';
                document.getElementById('scrollViewBtn').className = 'px-6 py-2 rounded-full font-medium transition-all flex items-center bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg';
                renderAllContent();
            }
            setTimeout(() => lucide.createIcons(), 100);
        }

        // 기본 정보 HTML
        function getBasicInfoHTML() {
            return `
                <div class="space-y-4 animate-fadeIn">
                    <div class="flex items-center space-x-4">
                        <label class="w-24 text-sm font-medium text-gray-700">학교</label>
                        <input type="text" value="${formData.school}"
                            onchange="updateFormData('school', this.value)"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="학교명 입력">
                    </div>

                    <div>
                        <div class="flex items-center space-x-4 mb-3">
                            <label class="w-24 text-sm font-medium text-gray-700">과정</label>
                            <div class="flex gap-2">
                                ${['초등', '중등', '고등'].map(level => `
                                    <button type="button" onclick="updateFormData('courseLevel', '${level}')"
                                        class="${formData.courseLevel === level
                                            ? 'px-4 py-2 rounded-lg font-medium transition-all bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg'
                                            : 'px-4 py-2 rounded-lg font-medium transition-all bg-gray-100 hover:bg-gray-200'}">
                                        ${level}
                                    </button>
                                `).join('')}
                            </div>
                        </div>

                        ${formData.courseLevel ? `
                            <div class="flex items-center space-x-4">
                                <label class="w-24 text-sm font-medium text-gray-700">학년</label>
                                <div class="flex gap-2">
                                    ${gradeOptions[formData.courseLevel].map(grade => `
                                        <button type="button" onclick="updateFormData('gradeDetail', '${grade}')"
                                            class="${formData.gradeDetail === grade
                                                ? 'px-4 py-2 rounded-lg font-medium transition-all bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg'
                                                : 'px-4 py-2 rounded-lg font-medium transition-all bg-white border border-gray-200 hover:border-purple-300'}">
                                            ${grade}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                    </div>

                    <div class="flex items-center space-x-4">
                        <label class="w-24 text-sm font-medium text-gray-700">학생이름</label>
                        <input type="text" value="${formData.studentName}"
                            onchange="updateFormData('studentName', this.value)"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="이름 입력">
                    </div>

                    <div class="flex items-center space-x-4">
                        <label class="w-24 text-sm font-medium text-gray-700">학생연락처</label>
                        <input type="tel" value="${formData.studentPhone}"
                            onchange="updateFormData('studentPhone', this.value)"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="010-0000-0000">
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-xs text-gray-600 mb-3">※ 부모님 연락처 (한 분만 입력 가능)</p>
                        <div class="flex items-center space-x-4">
                            <label class="w-24 text-sm font-medium text-gray-700">부모님연락처</label>
                            <div class="flex items-center space-x-2 flex-1">
                                <span class="text-sm text-gray-700">부</span>
                                <input type="tel" value="${formData.parentPhoneFather}"
                                    onchange="updateFormData('parentPhoneFather', this.value)"
                                    ${formData.parentPhoneMother !== '010-' ? 'disabled' : ''}
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent disabled:bg-gray-100"
                                    placeholder="010-0000-0000">
                                <span class="text-sm text-gray-700 ml-4">모</span>
                                <input type="tel" value="${formData.parentPhoneMother}"
                                    onchange="updateFormData('parentPhoneMother', this.value)"
                                    ${formData.parentPhoneFather !== '010-' ? 'disabled' : ''}
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent disabled:bg-gray-100"
                                    placeholder="010-0000-0000">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <label class="w-24 text-sm font-medium text-gray-700">주소</label>
                        <input type="text" value="${formData.address}"
                            onchange="updateFormData('address', this.value)"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="주소를 입력하세요">
                    </div>
                </div>
            `;
        }

        // 학습 진도 HTML
        function getLearningProgressHTML() {
            return `
                <div class="space-y-6 animate-fadeIn">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">학교 수학성적</label>
                        <div class="grid grid-cols-3 gap-3">
                            ${['상위권', '중위권', '수학이 어려워요'].map(level => `
                                <button type="button"
                                    onclick="updateFormData('mathLevel', '${level}'); this.style.backgroundColor='#8b5cf6'; setTimeout(() => { renderCurrentView(); }, 100);"
                                    class="btn-click ${formData.mathLevel === level
                                        ? 'p-4 rounded-xl font-medium transition-all transform bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-xl selected-pulse'
                                        : 'p-4 rounded-xl font-medium transition-all transform bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200 hover:shadow-lg'}"
                                    style="position: relative; z-index: 10;">
                                    ${level}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    ${getProgressSliderHTML('개념공부 진도', 'conceptLevel', 'conceptProgress', formData.conceptLevel, formData.conceptProgress)}
                    ${getProgressSliderHTML('심화학습 진도', 'advancedLevel', 'advancedProgress', formData.advancedLevel, formData.advancedProgress)}

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">참고사항</label>
                        <textarea
                            onchange="updateFormData('notes', this.value)"
                            onkeyup="clearTimeout(window.textSaveTimeout); window.textSaveTimeout = setTimeout(() => { updateFormData('notes', this.value); }, 1000);"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            rows="4" placeholder="학습에 참고할 사항을 입력하세요"
                            style="position: relative; z-index: 10;">${formData.notes}</textarea>
                    </div>
                </div>
            `;
        }

        // 진도 슬라이더 HTML
        function getProgressSliderHTML(label, levelField, progressField, levelValue, progressValue) {
            const currentOptions = progressOptions[levelValue];
            const sliderValue = progressValue || 0;

            return `
                <div class="mb-8">
                    <label class="text-sm font-medium text-gray-700 mb-3 block">
                        ${label}: <span class="text-purple-600 font-semibold">${currentOptions[sliderValue]}</span>
                    </label>

                    <div class="flex gap-2 mb-4">
                        ${['초등', '중등', '고등'].map(level => `
                            <button type="button"
                                onclick="this.style.transform='scale(0.9)'; this.style.backgroundColor='#a855f7'; updateProgressLevel('${levelField}', '${progressField}', '${level}'); setTimeout(() => { this.style.transform='scale(1)'; }, 150);"
                                class="btn-click ${levelValue === level
                                    ? 'px-4 py-2 rounded-lg font-medium transition-all bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg selected-pulse'
                                    : 'px-4 py-2 rounded-lg font-medium transition-all bg-gray-100 hover:bg-gray-200 hover:shadow-md'}"
                                style="position: relative; z-index: 15;">
                                ${level}
                            </button>
                        `).join('')}
                    </div>

                    <div class="relative">
                        <div class="relative h-2 bg-gray-200 rounded-full">
                            <div id="${progressField}_bar" class="absolute h-full bg-gradient-to-r from-blue-500 to-purple-600 rounded-full transition-all duration-100"
                                style="width: ${(sliderValue / (currentOptions.length - 1)) * 100}%"></div>
                        </div>

                        <input type="range"
                            id="${progressField}_slider"
                            min="0"
                            max="${currentOptions.length - 1}"
                            value="${sliderValue}"
                            onchange="updateFormData('${progressField}', parseInt(this.value))"
                            oninput="updateProgressDisplay('${levelValue}', '${progressField}', this.value); document.getElementById('${progressField}_bar').style.width = (this.value / ${currentOptions.length - 1}) * 100 + '%';"
                            onmousedown="this.style.opacity='0.2';"
                            onmouseup="this.style.opacity='0';"
                            class="absolute inset-0 w-full opacity-0 cursor-pointer transition-opacity"
                            style="height: 40px; top: -20px; z-index: 20;">

                        <div class="absolute inset-0 flex justify-between" style="pointer-events: none;">
                            ${currentOptions.map((option, index) => {
                                const position = (index / (currentOptions.length - 1)) * 100;
                                const isActive = index === sliderValue;
                                return `
                                    <div class="absolute transform -translate-x-1/2" style="left: ${position}%">
                                        <div class="${isActive
                                            ? 'w-4 h-4 rounded-full border-2 transition-all duration-300 bg-gradient-to-r from-purple-600 to-pink-600 border-purple-600 shadow-lg scale-125'
                                            : 'w-3 h-3 rounded-full border-2 transition-all duration-300 bg-white border-gray-300 hover:scale-110'}"></div>
                                        <div class="${isActive
                                            ? 'absolute top-6 left-1/2 transform -translate-x-1/2 text-center transition-all duration-300 leading-tight text-purple-600 font-bold vertical-text scale-110'
                                            : 'absolute top-6 left-1/2 transform -translate-x-1/2 text-center transition-all duration-300 leading-tight text-gray-400 vertical-text'}">
                                            ${option}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>

                        <div class="mt-24 text-center">
                            <span id="${progressField}_display" class="inline-block px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full text-sm font-semibold shadow-lg">
                                ${currentOptions[sliderValue]}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        }

        // 학습 스타일 HTML
        function getLearningStyleHTML() {
            return `
                <div class="space-y-6 animate-fadeIn">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">문제풀이 선호도</label>
                        <div class="grid grid-cols-3 gap-3">
                            ${['개념 정리 위주', '다양한 문제풀이', '고난도 심화 선호'].map(pref => `
                                <button type="button"
                                    onclick="this.style.transform='scale(0.95)'; this.style.backgroundColor='#6366f1'; updateFormData('problemPreference', '${pref}'); setTimeout(() => { renderCurrentView(); }, 100);"
                                    class="btn-click ${formData.problemPreference === pref
                                        ? 'p-3 rounded-lg font-medium transition-all bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-xl selected-pulse'
                                        : 'p-3 rounded-lg font-medium transition-all bg-white border border-gray-200 hover:border-blue-300 hover:shadow-lg'}"
                                    style="position: relative; z-index: 10;">
                                    ${pref}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">시험 대비 성향</label>
                        <div class="grid grid-cols-3 gap-3">
                            ${['벼락치기', '꾸준한 준비', '전략적 집중'].map(style => `
                                <button type="button"
                                    onclick="this.style.transform='scale(0.95)'; this.style.backgroundColor='#ec4899'; updateFormData('examStyle', '${style}'); setTimeout(() => { renderCurrentView(); }, 100);"
                                    class="btn-click ${formData.examStyle === style
                                        ? 'p-3 rounded-lg font-medium transition-all bg-gradient-to-r from-pink-500 to-purple-500 text-white shadow-xl selected-pulse'
                                        : 'p-3 rounded-lg font-medium transition-all bg-gray-50 hover:bg-gray-100 hover:shadow-lg'}"
                                    style="position: relative; z-index: 10;">
                                    ${style}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            수학 자신감: <span id="confidence_value" class="text-purple-600 font-bold">${formData.mathConfidence}</span>/10
                        </label>
                        <div class="relative">
                            <div class="h-3 bg-gray-200 rounded-full">
                                <div id="confidence_bar" class="h-full bg-gradient-to-r from-red-400 via-yellow-400 to-green-400 rounded-full"
                                    style="width: ${(formData.mathConfidence / 10) * 100}%; transition: none;"></div>
                            </div>
                            <input type="range"
                                min="0"
                                max="10"
                                value="${formData.mathConfidence}"
                                onchange="updateFormData('mathConfidence', parseInt(this.value))"
                                oninput="document.getElementById('confidence_value').textContent = this.value; document.getElementById('confidence_bar').style.width = (this.value / 10) * 100 + '%';"
                                class="absolute inset-0 w-full opacity-0 cursor-pointer"
                                style="position: relative; z-index: 10;">
                        </div>
                    </div>
                </div>
            `;
        }

        // 학습 방식 HTML
        function getLearningMethodHTML() {
            return `
                <div class="space-y-6 animate-fadeIn">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">부모님 학습 지도 스타일</label>
                        <div class="grid grid-cols-3 gap-3">
                            ${['적극 개입', '부분 지원', '자율 존중'].map(style => `
                                <button type="button"
                                    onclick="this.style.transform='scale(0.95)'; this.style.backgroundColor='#f97316'; updateFormData('parentStyle', '${style}'); setTimeout(() => { renderCurrentView(); }, 100);"
                                    class="btn-click ${formData.parentStyle === style
                                        ? 'p-3 rounded-lg font-medium transition-all bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-xl selected-pulse'
                                        : 'p-3 rounded-lg font-medium transition-all bg-gray-50 hover:bg-gray-100 hover:shadow-lg'}"
                                    style="position: relative; z-index: 10;">
                                    ${style}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">학습 스트레스</label>
                        <div class="grid grid-cols-3 gap-3">
                            ${['낮음', '보통', '높음'].map(level => `
                                <button type="button"
                                    onclick="this.style.transform='scale(0.95)'; this.style.backgroundColor='#14b8a6'; updateFormData('stressLevel', '${level}'); setTimeout(() => { renderCurrentView(); }, 100);"
                                    class="btn-click ${formData.stressLevel === level
                                        ? 'p-3 rounded-lg font-medium transition-all bg-gradient-to-r from-blue-500 to-teal-500 text-white shadow-xl selected-pulse'
                                        : 'p-3 rounded-lg font-medium transition-all bg-gray-50 hover:bg-gray-100 hover:shadow-lg'}"
                                    style="position: relative; z-index: 10;">
                                    ${level}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">피드백 선호 방식</label>
                        <div class="grid grid-cols-3 gap-3">
                            ${[
                                {key: '직접', label: '직접', desc: '선생님 1:1 설명'},
                                {key: '컨텐츠', label: '컨텐츠', desc: '동영상, AI 설명 등'},
                                {key: '해설지', label: '해설지 제공', desc: '문제 해설지'}
                            ].map(pref => `
                                <button type="button"
                                    onclick="this.style.transform='scale(0.95)'; this.style.backgroundColor='#6366f1'; updateFormData('feedbackPreference', '${pref.key}'); setTimeout(() => { renderCurrentView(); }, 100);"
                                    class="btn-click ${formData.feedbackPreference === pref.key
                                        ? 'p-3 rounded-lg transition-all bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-xl selected-pulse'
                                        : 'p-3 rounded-lg transition-all bg-white border border-gray-200 hover:border-purple-300 hover:shadow-lg'}"
                                    style="position: relative; z-index: 10;">
                                    <div class="font-medium">${pref.label}</div>
                                    <div class="text-xs mt-1 opacity-80">${pref.desc}</div>
                                </button>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
        }

        // 목표 설정 HTML
        function getGoalSettingHTML() {
            const goalOptions = {
                '단기': [
                    '이번 시험에서 점수 올리기',
                    '틀린 문제 줄이기',
                    '숙제 빠짐없이 하기',
                    '시험 범위 개념 다시 확인하기',
                    '오답노트 만들어 보기'
                ],
                '중기': [
                    '교과서 개념 다 이해하기',
                    '단원별 문제집 풀어보기',
                    '어려운 문제도 혼자 풀어보기',
                    '꾸준히 공부하는 습관 만들기',
                    '수학에 대한 자신감 기르기'
                ],
                '장기': [
                    '수학을 잘해서 원하는 학교 가기',
                    '경시대회 준비해 보기',
                    '심화 문제도 풀 수 있는 실력 쌓기',
                    '수학을 좋아하게 되기',
                    '긴 목표를 두고 꾸준히 공부하기'
                ]
            };

            return `
                <div class="space-y-6 animate-fadeIn">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">📌 단기 목표 (시험 대비 · 성적 향상)</h4>
                        ${formData.shortTermGoal ? `
                            <div class="p-4 bg-blue-50 rounded-lg mb-3">
                                <p class="text-sm text-blue-700 font-medium">✅ 선택된 목표: ${formData.shortTermGoal}</p>
                            </div>
                        ` : `
                            <div class="p-4 bg-purple-50 rounded-lg mb-3">
                                <p class="text-sm text-purple-700 italic">이번 학기 중간고사에서 좋은 성적을 받고 싶어요!</p>
                            </div>
                        `}
                        <div id="shortTermGoalContainer">
                            <div class="space-y-2">
                                ${goalOptions['단기'].map((option, index) => `
                                    <button type="button"
                                        onclick="this.style.transform='scale(0.98)'; this.style.backgroundColor='#3b82f6'; this.style.color='white'; selectGoal('shortTermGoal', '${option}')"
                                        class="btn-click w-full p-3 rounded-lg text-left transition-all ${formData.shortTermGoal === option
                                            ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-xl border-2 border-blue-400'
                                            : 'bg-white border border-gray-200 hover:border-blue-300 hover:bg-blue-50 hover:shadow-lg'}"
                                        style="position: relative; z-index: 10;">
                                        <span class="text-sm flex items-center">
                                            ${formData.shortTermGoal === option ? '✅ ' : ''}
                                            ${index + 1}. ${option}
                                        </span>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    </div>

                    ${formData.shortTermGoal ? `
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">📌 중기 목표 (개념 완성 · 심화 학습)</h4>
                            ${formData.midTermGoal ? `
                                <div class="p-4 bg-green-50 rounded-lg mb-3">
                                    <p class="text-sm text-green-700 font-medium">✅ 선택된 목표: ${formData.midTermGoal}</p>
                                </div>
                            ` : `
                                <div class="p-4 bg-purple-50 rounded-lg mb-3">
                                    <p class="text-sm text-purple-700 italic">수학 개념을 완벽하게 이해하고 심화 학습을 하고 싶어요!</p>
                                </div>
                            `}
                            <div id="midTermGoalContainer">
                                <div class="space-y-2">
                                    ${goalOptions['중기'].map((option, index) => `
                                        <button type="button"
                                            onclick="this.style.transform='scale(0.98)'; this.style.backgroundColor='#10b981'; this.style.color='white'; selectGoal('midTermGoal', '${option}')"
                                            class="btn-click w-full p-3 rounded-lg text-left transition-all ${formData.midTermGoal === option
                                                ? 'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-xl border-2 border-green-400'
                                                : 'bg-white border border-gray-200 hover:border-green-300 hover:bg-green-50 hover:shadow-lg'}"
                                            style="position: relative; z-index: 10;">
                                            <span class="text-sm flex items-center">
                                                ${formData.midTermGoal === option ? '✅ ' : ''}
                                                ${index + 1}. ${option}
                                            </span>
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    ` : ''}

                    ${formData.midTermGoal ? `
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">📌 장기 목표 (상위권 진학 · 올림피아드)</h4>
                            ${formData.longTermGoal ? `
                                <div class="p-4 bg-purple-50 rounded-lg mb-3">
                                    <p class="text-sm text-purple-700 font-medium">✅ 선택된 목표: ${formData.longTermGoal}</p>
                                </div>
                            ` : `
                                <div class="p-4 bg-purple-50 rounded-lg mb-3">
                                    <p class="text-sm text-purple-700 italic">명문대 진학과 수학 올림피아드 도전을 준비하고 있어요!</p>
                                </div>
                            `}
                            <div id="longTermGoalContainer">
                                <div class="space-y-2">
                                    ${goalOptions['장기'].map((option, index) => `
                                        <button type="button"
                                            onclick="this.style.transform='scale(0.98)'; this.style.backgroundColor='#a855f7'; this.style.color='white'; selectGoal('longTermGoal', '${option}')"
                                            class="btn-click w-full p-3 rounded-lg text-left transition-all ${formData.longTermGoal === option
                                                ? 'bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-xl border-2 border-purple-400'
                                                : 'bg-white border border-gray-200 hover:border-purple-300 hover:bg-purple-50 hover:shadow-lg'}"
                                            style="position: relative; z-index: 10;">
                                            <span class="text-sm flex items-center">
                                                ${formData.longTermGoal === option ? '✅ ' : ''}
                                                ${index + 1}. ${option}
                                            </span>
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    ` : ''}

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">참고사항</label>
                        <input type="text" value="${formData.goalNote}"
                            onchange="updateFormData('goalNote', this.value)"
                            onkeyup="clearTimeout(window.goalNoteSaveTimeout); window.goalNoteSaveTimeout = setTimeout(() => { updateFormData('goalNote', this.value); }, 1000);"
                            onfocus="this.style.borderColor='#a855f7'; this.style.boxShadow='0 0 0 3px rgba(168, 85, 247, 0.1)';"
                            onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                            placeholder="추가로 전달하고 싶은 목표가 있다면 입력해주세요"
                            style="position: relative; z-index: 10;">
                    </div>
                </div>
            `;
        }

        // 추가 정보 HTML
        function getAdditionalInfoHTML() {
            return `
                <div class="space-y-6 animate-fadeIn">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            평소 주당 학습시간: <span id="weeklyHours_value" class="text-blue-600 font-bold">${formData.weeklyHours}</span>시간
                        </label>
                        <div class="relative">
                            <div class="h-3 bg-gray-200 rounded-full">
                                <div id="weeklyHours_bar" class="h-full bg-gradient-to-r from-green-400 to-blue-500 rounded-full"
                                    style="width: ${(formData.weeklyHours / 30) * 100}%; transition: none;"></div>
                            </div>
                            <input type="range"
                                min="0"
                                max="30"
                                value="${formData.weeklyHours}"
                                onchange="updateFormData('weeklyHours', parseInt(this.value))"
                                oninput="document.getElementById('weeklyHours_value').textContent = this.value; document.getElementById('weeklyHours_bar').style.width = (this.value / 30) * 100 + '%';"
                                class="absolute inset-0 w-full opacity-0 cursor-pointer"
                                style="position: relative; z-index: 10;">
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span>0시간</span>
                            <span>15시간</span>
                            <span>30시간</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">과거 학원/과외 경험 (총 경험 기간)</label>
                        <div class="grid grid-cols-4 gap-2">
                            ${['처음', '1년이상', '2년이상', '3년이상', '4년이상', '5년이상', '6년이상'].map(exp => `
                                <button type="button"
                                    onclick="this.style.transform='scale(0.95)'; this.style.backgroundColor='#ec4899'; updateFormData('academyExperience', '${exp}'); setTimeout(() => { renderCurrentView(); }, 100);"
                                    class="btn-click ${formData.academyExperience === exp
                                        ? 'p-2.5 rounded-lg text-sm font-medium transition-all bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-xl selected-pulse'
                                        : 'p-2.5 rounded-lg text-sm font-medium transition-all bg-gray-50 hover:bg-gray-100 hover:shadow-lg'}"
                                    style="position: relative; z-index: 10;">
                                    ${exp}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">좋아하는 음식</label>
                            <input type="text" value="${formData.favoriteFood}"
                                onchange="updateFormData('favoriteFood', this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="예: 파스타, 치킨, 짜장면">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">좋아하는 과일</label>
                            <input type="text" value="${formData.favoriteFruit}"
                                onchange="updateFormData('favoriteFruit', this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="예: 딸기, 사과, 포도">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">좋아하는 과자</label>
                            <input type="text" value="${formData.favoriteSnack}"
                                onchange="updateFormData('favoriteSnack', this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="예: 포카칩, 오레오, 초코파이">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">취미/관심분야</label>
                            <input type="text" value="${formData.hobbiesInterests}"
                                onchange="updateFormData('hobbiesInterests', this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="예: 축구, 피아노, 코딩, 애니메이션">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">덕질 여부</label>
                        <div class="grid grid-cols-2 gap-3">
                            ${[
                                { key: true, label: '예' },
                                { key: false, label: '아니오' }
                            ].map(opt => `
                                <button type="button"
                                    onclick="this.style.transform='scale(0.95)'; updateFormData('fandomYN', ${opt.key}); setTimeout(() => { renderCurrentView(); }, 100);"
                                    class="btn-click ${String(formData.fandomYN) === String(opt.key)
                                        ? 'p-3 rounded-lg text-sm font-medium transition-all bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-xl selected-pulse'
                                        : 'p-3 rounded-lg text-sm font-medium transition-all bg-gray-50 hover:bg-gray-100 hover:shadow-lg'}"
                                    style="position: relative; z-index: 10;">
                                    ${opt.label}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox"
                                ${formData.dataConsent ? 'checked' : ''}
                                onchange="updateFormData('dataConsent', this.checked)"
                                class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                            <span class="text-sm text-gray-700">
                                학생 학습 데이터 추적·분석에 동의합니다
                            </span>
                        </label>
                    </div>
                </div>
            `;
        }

        // 스크롤 뷰 전체 컨텐츠 렌더링
        function renderAllContent() {
            document.getElementById('basicInfoContent').innerHTML = getBasicInfoHTML();
            document.getElementById('learningProgressContent').innerHTML = getLearningProgressHTML();
            document.getElementById('learningStyleContent').innerHTML = getLearningStyleHTML();
            document.getElementById('learningMethodContent').innerHTML = getLearningMethodHTML();
            document.getElementById('goalSettingContent').innerHTML = getGoalSettingHTML();
            document.getElementById('additionalInfoContent').innerHTML = getAdditionalInfoHTML();

            setTimeout(() => {
                lucide.createIcons();
            }, 100);
        }

        // 탭 뷰 렌더링 함수
        function renderTabContent(tabIndex) {
            activeTab = tabIndex;
            const tabContent = document.getElementById('tabContent');
            const tabs = ['기본 정보', '학습 진도', '학습 스타일', '학습 방식', '목표 설정', '추가 정보'];

            // 탭 버튼 업데이트
            document.querySelectorAll('.tab-btn').forEach((btn, index) => {
                if (index === tabIndex) {
                    btn.className = 'tab-btn px-4 py-3 rounded-xl font-medium transition-all flex flex-col items-center bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg transform scale-105';
                } else {
                    btn.className = 'tab-btn px-4 py-3 rounded-xl font-medium transition-all flex flex-col items-center text-gray-600 hover:bg-gray-100';
                }
            });

            // 콘텐츠 렌더링
            let content = '';
            switch(tabIndex) {
                case 0:
                    content = getBasicInfoHTML();
                    break;
                case 1:
                    content = getLearningProgressHTML();
                    break;
                case 2:
                    content = getLearningStyleHTML();
                    break;
                case 3:
                    content = getLearningMethodHTML();
                    break;
                case 4:
                    content = getGoalSettingHTML();
                    break;
                case 5:
                    content = getAdditionalInfoHTML();
                    break;
            }

            tabContent.innerHTML = `
                <h3 class="text-xl font-bold text-gray-800 mb-6">${tabs[tabIndex]}</h3>
                ${content}
            `;

            // 네비게이션 버튼 업데이트
            updateNavigationButtons();

            setTimeout(() => {
                lucide.createIcons();
            }, 100);
        }

        // 이전 탭으로 이동
        function previousTab() {
            if (activeTab > 0) {
                renderTabContent(activeTab - 1);
            }
        }

        // 다음 탭으로 이동
        function nextTab() {
            if (activeTab < 5) {
                renderTabContent(activeTab + 1);
            } else {
                // 마지막 탭에서 제출
                document.getElementById('onboardingForm').submit();
            }
        }

        // 네비게이션 버튼 업데이트
        function updateNavigationButtons() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            // 이전 버튼
            if (activeTab === 0) {
                prevBtn.className = 'px-6 py-3 bg-gray-200 text-gray-400 rounded-lg font-medium transition-all flex items-center cursor-not-allowed';
                prevBtn.disabled = true;
            } else {
                prevBtn.className = 'px-6 py-3 bg-gray-300 text-gray-700 rounded-lg font-medium transition-all flex items-center hover:bg-gray-400';
                prevBtn.disabled = false;
            }

            // 다음 버튼
            if (activeTab === 5) {
                nextBtn.innerHTML = '<i data-lucide="check" class="mr-2 w-5 h-5"></i> 완료';
                nextBtn.className = 'px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg font-medium transition-all flex items-center hover:from-green-600 hover:to-emerald-700 shadow-lg';
            } else {
                nextBtn.innerHTML = '다음 <i data-lucide="chevron-right" class="ml-2 w-5 h-5"></i>';
                nextBtn.className = 'px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg font-medium transition-all flex items-center hover:from-purple-600 hover:to-pink-600 shadow-lg';
            }
        }

        // 특정 탭으로 이동
        function goToTab(tabIndex) {
            renderTabContent(tabIndex);
        }

        // Helper Functions
        function updateFormData(field, value) {
            formData[field] = value;
            updateHiddenInputs();

            if (field === 'parentPhoneFather' && value !== '010-') {
                formData.parentPhoneMother = '010-';
            } else if (field === 'parentPhoneMother' && value !== '010-') {
                formData.parentPhoneFather = '010-';
            }

            if (field === 'courseLevel') {
                formData.gradeDetail = '';
                // 현재 뷰 모드에 따라 적절히 렌더링
                if (viewMode === 'tab') {
                    renderTabContent(activeTab);
                } else {
                    renderAllContent();
                }
            }

            // 즉각적인 시각 피드백
            addClickFeedback();

            // 자동 저장 호출
            autoSave(field, value);
        }

        // 자동 저장 함수 (디바운스 적용)
        let saveTimeout = null;
        function autoSave(field, value) {
            // 기존 타이머 취소
            if (saveTimeout) {
                clearTimeout(saveTimeout);
            }

            // 저장 중 표시
            showSaveStatus('saving');

            // 0.5초 후 저장 (디바운스)
            saveTimeout = setTimeout(() => {
                // 전체 폼 데이터 준비
                const saveData = {
                    user_id: <?php echo $user_id; ?>,
                    ...formData
                };

                // 현재 필드 업데이트
                saveData[field] = value;

                $.ajax({
                    url: 'save_onboarding.php',
                    method: 'POST',
                    data: { ...saveData, ajax: '1' },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success !== false) {
                            showSaveStatus('saved');
                            console.log('자동 저장 완료:', field, value);
                        } else {
                            showSaveStatus('error');
                            console.error('저장 실패:', response?.message || '알 수 없는 오류');
                        }
                    },
                    error: function(xhr, status, error) {
                        // JSON 파싱 시도
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.success === false) {
                                showSaveStatus('error');
                                console.error('저장 실패:', response.message || response.error);
                            } else {
                                showSaveStatus('saved');
                                console.log('자동 저장 완료:', field, value);
                            }
                        } catch (e) {
                            // JSON 파싱 실패 시에도 성공으로 간주 (기존 save_onboarding.php는 JSON을 반환하지 않을 수 있음)
                            if (xhr.status === 200) {
                                showSaveStatus('saved');
                                console.log('자동 저장 완료:', field, value);
                            } else {
                                showSaveStatus('error');
                                console.error('AJAX 오류:', error, xhr.responseText);
                            }
                        }
                    }
                });
            }, 500);
        }

        // 저장 상태 표시 함수
        function showSaveStatus(status) {
            let saveIndicator = document.getElementById('saveIndicator');

            if (!saveIndicator) {
                // 저장 상태 표시기 생성
                saveIndicator = document.createElement('div');
                saveIndicator.id = 'saveIndicator';
                saveIndicator.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 10px 20px; border-radius: 8px; font-size: 14px; z-index: 9999; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;';
                document.body.appendChild(saveIndicator);
            }

            switch(status) {
                case 'saving':
                    saveIndicator.innerHTML = '<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> 저장 중...';
                    saveIndicator.style.backgroundColor = '#3b82f6';
                    saveIndicator.style.color = 'white';
                    saveIndicator.style.opacity = '1';
                    break;

                case 'saved':
                    saveIndicator.innerHTML = '✅ 저장됨';
                    saveIndicator.style.backgroundColor = '#10b981';
                    saveIndicator.style.color = 'white';
                    saveIndicator.style.opacity = '1';

                    // 2초 후 페이드아웃
                    setTimeout(() => {
                        saveIndicator.style.opacity = '0';
                    }, 2000);
                    break;

                case 'error':
                    saveIndicator.innerHTML = '❌ 저장 실패';
                    saveIndicator.style.backgroundColor = '#ef4444';
                    saveIndicator.style.color = 'white';
                    saveIndicator.style.opacity = '1';

                    // 3초 후 페이드아웃
                    setTimeout(() => {
                        saveIndicator.style.opacity = '0';
                    }, 3000);
                    break;
            }
        }

        // 클릭 시 즉각적인 피드백 추가
        function addClickFeedback() {
            // 모든 버튼에 플래시 효과
            event?.target?.classList.add('flash-effect');
            setTimeout(() => {
                event?.target?.classList.remove('flash-effect');
            }, 300);
        }

        // 리플 효과 추가
        function addRipple(element, event) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');

            const rect = element.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';

            element.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        }

        function updateProgressLevel(levelField, progressField, level) {
            formData[levelField] = level;
            formData[progressField] = Math.floor(progressOptions[level].length / 2);
            updateHiddenInputs();

            // 자동 저장
            autoSave(levelField, level);
            autoSave(progressField, formData[progressField]);

            renderCurrentView();
        }

        function updateProgressDisplay(levelValue, progressField, value) {
            formData[progressField] = parseInt(value);
            const display = document.getElementById(`${progressField}_display`);
            if (display) {
                display.textContent = progressOptions[levelValue][value];
                // 즉각적인 색상 변화와 크기 변화
                display.style.backgroundColor = '#8b5cf6';
                display.style.transform = 'scale(1.1)';
                display.style.transition = 'all 0.2s ease';

                // 애니메이션 효과 추가
                display.classList.add('animate-pulse');
                setTimeout(() => {
                    display.style.backgroundColor = '';
                    display.style.transform = 'scale(1)';
                    display.classList.remove('animate-pulse');
                }, 400);
            }

            // 슬라이더 바 즉시 업데이트
            updateSliderBar(progressField, value, progressOptions[levelValue].length - 1);
            updateHiddenInputs();

            // 자동 저장 (디바운스 적용)
            clearTimeout(window.sliderSaveTimeout);
            window.sliderSaveTimeout = setTimeout(() => {
                autoSave(progressField, value);
            }, 500); // 슬라이더는 0.5초 후 저장
        }

        // 슬라이더 바 즉시 업데이트 함수
        function updateSliderBar(progressField, value, maxValue) {
            // 진도 바 ID로 직접 찾기
            const progressBar = document.getElementById(`${progressField}_bar`);
            if (progressBar) {
                const percentage = (parseInt(value) / maxValue) * 100;
                progressBar.style.width = `${percentage}%`;
                // transition 제거하여 즉시 업데이트
                progressBar.style.transition = 'none';
                setTimeout(() => {
                    progressBar.style.transition = 'width 0.1s ease-out';
                }, 10);
            }

            // 점들 업데이트 - 나중에 필요시 구현
            const slider = document.getElementById(`${progressField}_slider`);
            if (slider && slider.parentElement) {
                const dots = slider.parentElement.querySelectorAll('.absolute.transform');
                dots.forEach((dot, index) => {
                    const dotIndicator = dot.querySelector('div:first-child');
                    const dotText = dot.querySelector('div:last-child');
                    if (index == value) {
                        // 선택된 점
                        if (dotIndicator) {
                            dotIndicator.className = 'w-4 h-4 rounded-full border-2 transition-all duration-100 bg-gradient-to-r from-purple-600 to-pink-600 border-purple-600 shadow-lg scale-125';
                        }
                        if (dotText) {
                            dotText.className = 'absolute top-6 left-1/2 transform -translate-x-1/2 text-center transition-all duration-100 leading-tight text-purple-600 font-bold vertical-text scale-110';
                        }
                    } else {
                        // 선택되지 않은 점
                        if (dotIndicator) {
                            dotIndicator.className = 'w-3 h-3 rounded-full border-2 transition-all duration-100 bg-white border-gray-300 hover:scale-110';
                        }
                        if (dotText) {
                            dotText.className = 'absolute top-6 left-1/2 transform -translate-x-1/2 text-center transition-all duration-100 leading-tight text-gray-400 vertical-text';
                        }
                    }
                });
            }
        }

        function updateHiddenInputs() {
            Object.keys(formData).forEach(key => {
                const hiddenInput = document.getElementById(key + '_hidden');
                if (hiddenInput) {
                    if (typeof formData[key] === 'boolean') {
                        hiddenInput.value = formData[key] ? '1' : '0';
                    } else {
                        hiddenInput.value = formData[key];
                    }
                }
            });
        }

        function updateConfidenceDisplay(value) {
            formData.mathConfidence = parseInt(value);
            if (document.getElementById('confidence_value')) {
                document.getElementById('confidence_value').textContent = value;
                document.getElementById('confidence_bar').style.width = `${(value / 10) * 100}%`;
            }
            updateHiddenInputs();
        }

        function updateWeeklyHoursDisplay(value) {
            formData.weeklyHours = parseInt(value);
            if (document.getElementById('weeklyHours_value')) {
                document.getElementById('weeklyHours_value').textContent = value;
                document.getElementById('weeklyHours_bar').style.width = `${(value / 30) * 100}%`;
            }
            updateHiddenInputs();
        }

        function selectGoal(goalType, value) {
            formData[goalType] = value;
            updateHiddenInputs();

            // 자동 저장 추가
            autoSave(goalType, value);

            renderCurrentView();
        }

        // 페이지 로드시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            // Lucide 아이콘 초기화
            lucide.createIcons();

            // 초기 값 설정
            updateHiddenInputs();

            // 스크롤 뷰로 시작
            setViewMode('scroll');

            // 폼 제출 시 hidden inputs 업데이트
            const form = document.getElementById('onboardingForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    updateHiddenInputs();
                });
            }
        });

    </script>
</body>
</html>