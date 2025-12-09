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

// 학년 계산 함수
function calculateGrade($birthYear) {
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
        if ($info['level'] == 'high') {
            return '고' . $info['grade'];
        } else if ($info['level'] == 'middle') {
            return '중' . $info['grade'];
        } else {
            return '초' . $info['grade'];
        }
    }
    
    return '';
}

// 시험 종류 자동 판단
$currentMonth = intval(date('n'));
$currentDay = intval(date('j'));
$currentYear = intval(date('Y'));
$examType = '';
$representativeDate = '';

// 디버그 정보
error_log("Current date for exam type: Month=$currentMonth, Day=$currentDay");

// 날짜 기반 시험 종류 판단 및 대표성 있는 날짜 설정
if ($currentMonth == 12 && $currentDay >= 11) {
    $examType = '1mid'; // 12월 11일부터 1학기 중간고사
    $representativeDate = ($currentYear + 1) . '-05-01'; // 다음해 5월 1일
} else if ($currentMonth >= 1 && $currentMonth <= 4) {
    $examType = '1mid'; // 1월~4월은 1학기 중간고사
    $representativeDate = $currentYear . '-05-01'; // 5월 1일
} else if ($currentMonth == 5 && $currentDay == 1) {
    $examType = '1mid'; // 5월 1일까지 1학기 중간고사
    $representativeDate = $currentYear . '-05-01'; // 5월 1일
} else if ($currentMonth == 5 && $currentDay >= 2) {
    $examType = '1final'; // 5월 2일부터 1학기 기말고사
    $representativeDate = $currentYear . '-07-01'; // 7월 1일
} else if ($currentMonth == 6) {
    $examType = '1final'; // 6월은 1학기 기말고사
    $representativeDate = $currentYear . '-07-01'; // 7월 1일
} else if ($currentMonth >= 7 && $currentMonth <= 9) {
    $examType = '2mid'; // 7월~9월은 2학기 중간고사
    $representativeDate = $currentYear . '-10-01'; // 10월 1일
} else if ($currentMonth >= 10 && $currentMonth <= 11) {
    $examType = '2final'; // 10월~11월은 2학기 기말고사
    $representativeDate = $currentYear . '-12-10'; // 12월 10일
} else if ($currentMonth == 12 && $currentDay <= 10) {
    $examType = '2final'; // 12월 10일까지 2학기 기말고사
    $representativeDate = $currentYear . '-12-10'; // 12월 10일
}

error_log("Calculated exam type: $examType, Representative date: $representativeDate");

$semester = (strpos($examType, '1') === 0) ? 1 : 2;

// LMS 데이터에서 학교와 학년 정보 우선 사용
$school = $lms_data['institute'] ?: ($exam_info ? $exam_info->school : '');
$birthYear = intval($lms_data['birthdate']);
$grade = '';
if ($birthYear > 0) {
    $grade = calculateGrade($birthYear);
} else if ($exam_info) {
    $grade = $exam_info->grade;
}

$user_json = array(
    'userid' => $userid,
    'username' => $user->username,
    'firstname' => $user->firstname,
    'lastname' => $user->lastname,
    'school' => $school,
    'grade' => $grade,
    'birthYear' => $birthYear,
    'semester' => $semester,
    // 기존 시험 정보가 현재 날짜에 맞지 않으면 자동 계산된 값 사용
    'examType' => '',  // 항상 비워두고 JavaScript에서 처리
    'savedExamType' => $exam_info && $exam_info->exam_type ? $exam_info->exam_type : '',
    'defaultExamType' => $examType,
    'representativeDate' => $representativeDate,
    'examStartDate' => $exam_info ? $exam_info->exam_start_date : '',
    'examEndDate' => $exam_info ? $exam_info->exam_end_date : '',
    'mathExamDate' => $exam_info ? $exam_info->math_exam_date : '',
    'examScope' => $exam_info ? $exam_info->exam_scope : '',
    'examStatus' => $exam_info ? $exam_info->exam_status : 'expected',
    'studyStatus' => $exam_info ? $exam_info->study_status : '',
    'lmsData' => $lms_data
); 
 
// 학습 대시보드를 위한 goals 데이터 가져오기
$dashboardGoals = array('success' => false);
if (file_exists(__DIR__ . '/get_dashboard_goals.php')) {
    require_once(__DIR__ . '/get_dashboard_goals.php');
    try {
        $dashboardGoals = getDashboardGoals($userid);
    } catch (Exception $e) {
        $dashboardGoals = array(
            'success' => true,
            'today_goal' => null,
            'weekly_goal' => null,
            'recent_goals' => array(),
            'quarter_goals' => array(),
            'quiz_data' => array(
                'internal_tests' => array(),
                'standard_tests' => array()
            ),
            'roadmap_missions' => array()
        );
    }
}

// 디버깅용 - 데이터 확인
error_log('=== index.php 데이터 확인 ===');
error_log('userid: ' . $userid);
error_log('exam_info 전체: ' . print_r($exam_info, true));
if ($exam_info) {
    error_log('exam_start_date: ' . $exam_info->exam_start_date);
    error_log('exam_end_date: ' . $exam_info->exam_end_date);
    error_log('math_exam_date: ' . $exam_info->math_exam_date);
    error_log('exam_scope: ' . $exam_info->exam_scope);
    error_log('exam_status: ' . $exam_info->exam_status);
}
error_log('user_json: ' . print_r($user_json, true));
// error_log('Dashboard Goals Data: ' . json_encode($dashboardGoals));
// error_log('Exam Info Data: ' . json_encode($exam_info));
// error_log('User JSON Data: ' . json_encode($user_json));

// 디버그: 현재 날짜와 자동 계산된 시험 종류 표시
echo "<!-- Debug: Current Month=$currentMonth, Day=$currentDay, Calculated Exam Type=$examType -->\n";

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학교 시험대비 에이전트</title>
    <style>
        /* 기본 설정 및 유틸리티 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* 스크롤바 스타일링 */
        #ai-chat-messages::-webkit-scrollbar {
            width: 8px;
        }
        
        #ai-chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        } 
        
        #ai-chat-messages::-webkit-scrollbar-thumb {
            background: #c084fc;
            border-radius: 10px;
        }
        
        #ai-chat-messages::-webkit-scrollbar-thumb:hover {
            background: #a855f7;
        }
        
        /* Firefox 스크롤바 */
        #ai-chat-messages {
            scrollbar-width: thin;
            scrollbar-color: #c084fc #f1f1f1;
        }
         
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            line-height: 1.6;
        }
        
        /* 색상 변수 */
        :root {
            --blue-50: #eff6ff;
            --blue-100: #dbeafe;
            --blue-200: #bfdbfe;
            --blue-400: #60a5fa;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --indigo-50: #eef2ff;
            --indigo-100: #e0e7ff;
            --indigo-200: #c7d2fe;
            --indigo-500: #6366f1;
            --indigo-600: #4f46e5;
            --indigo-700: #4338ca;
            --indigo-800: #3730a3;
            --purple-50: #faf5ff;
            --purple-100: #f3e8ff;
            --purple-200: #e9d5ff;
            --purple-500: #a855f7;
            --purple-600: #9333ea;
            --purple-700: #7c3aed;
            --purple-800: #6b21a8;
            --purple-900: #581c87;
            --pink-600: #db2777;
            --green-50: #f0fdf4;
            --green-100: #dcfce7;
            --green-200: #bbf7d0;
            --green-500: #22c55e;
            --green-600: #16a34a;
            --green-700: #15803d;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --slate-900: #0f172a;
            --yellow-300: #fde047;
            --yellow-500: #eab308;
            --orange-500: #f97316;
            --orange-600: #ea580c;
            --red-500: #ef4444;
            --red-600: #dc2626;
            --emerald-600: #059669;
            --teal-600: #0d9488;
        }

        /* 레이아웃 클래스 */
        .h-screen { height: 100vh; }
        .min-h-screen { min-height: 100vh; }
        .w-full { width: 100%; }
        .max-w-3xl { max-width: 48rem; }
        .max-w-4xl { max-width: 56rem; }
        .max-w-6xl { max-width: 72rem; }
        .max-w-2xl { max-width: 42rem; }
        .max-w-lg { max-width: 32rem; }
        .max-w-xs { max-width: 20rem; }
        .max-w-md { max-width: 28rem; }
        .h-8 { height: 2rem; }
        .h-12 { height: 3rem; }
        .h-24 { height: 6rem; }
        .h-96 { height: 24rem; }
        .h-2 { height: 0.5rem; }
        .h-4 { height: 1rem; }
        .h-5 { height: 1.25rem; }
        .h-6 { height: 1.5rem; }
        .max-h-96 { max-height: 24rem; }
        .max-h-32 { max-height: 8rem; }
        .max-h-[80vh] { max-height: 80vh; }
        .max-h-[70vh] { max-height: 70vh; }
        .w-5 { width: 1.25rem; }
        .w-6 { width: 1.5rem; }
        .w-10 { width: 2.5rem; }
        .w-24 { width: 6rem; }

        /* Flexbox */
        .flex { display: flex; }
        .flex-1 { flex: 1 1 0%; }
        .flex-col { flex-direction: column; }
        .items-center { align-items: center; }
        .items-start { align-items: flex-start; }
        .justify-center { justify-content: center; }
        .justify-between { justify-content: space-between; }
        .gap-1 { gap: 0.25rem; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 0.75rem; }
        .gap-4 { gap: 1rem; }
        .gap-6 { gap: 1.5rem; }
        .gap-8 { gap: 2rem; }

        /* Grid */
        .grid { display: grid; }
        .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-cols-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
        .col-span-3 { grid-column: span 3 / span 3; }
        .col-span-6 { grid-column: span 6 / span 6; }

        /* 여백 */
        .p-2 { padding: 0.5rem; }
        .p-3 { padding: 0.75rem; }
        .p-4 { padding: 1rem; }
        .p-6 { padding: 1.5rem; }
        .p-8 { padding: 2rem; }
        .p-10 { padding: 2.5rem; }
        .px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
        .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .py-5 { padding-top: 1.25rem; padding-bottom: 1.25rem; }
        .pt-20 { padding-top: 5rem; }
        .pb-2 { padding-bottom: 0.5rem; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-8 { margin-bottom: 2rem; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .ml-2 { margin-left: 0.5rem; }
        .ml-7 { margin-left: 1.75rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }

        /* 위치 */
        .fixed { position: fixed; }
        .relative { position: relative; }
        .absolute { position: absolute; }
        .top-0 { top: 0px; }
        .left-0 { left: 0px; }
        .right-0 { right: 0px; }
        .bottom-8 { bottom: 2rem; }
        .right-8 { right: 2rem; }
        .inset-0 { top: 0px; right: 0px; bottom: 0px; left: 0px; }
        .-top-1 { top: -0.25rem; }
        .-right-1 { right: -0.25rem; }

        /* z-index */
        .z-30 { z-index: 30; }
        .z-40 { z-index: 40; }
        .z-50 { z-index: 50; }

        /* 배경 */
        .bg-white { background-color: #ffffff; }
        .bg-gray-50 { background-color: var(--gray-50); }
        .bg-gray-100 { background-color: var(--gray-100); }
        .bg-gray-200 { background-color: var(--gray-200); }
        .bg-blue-50 { background-color: var(--blue-50); }
        .bg-blue-100 { background-color: var(--blue-100); }
        .bg-green-50 { background-color: var(--green-50); }
        .bg-green-100 { background-color: var(--green-100); }
        .bg-indigo-50 { background-color: var(--indigo-50); }
        .bg-indigo-100 { background-color: var(--indigo-100); }
        .bg-indigo-600 { background-color: var(--indigo-600); }
        .bg-purple-100 { background-color: var(--purple-100); }
        .bg-red-50 { background-color: var(--red-50); }
        .bg-red-500 { background-color: var(--red-500); }
        .bg-gradient-to-b { background-image: linear-gradient(to bottom, var(--tw-gradient-stops)); }
        .bg-gradient-to-r { background-image: linear-gradient(to right, var(--tw-gradient-stops)); }
        .bg-gradient-to-br { background-image: linear-gradient(to bottom right, var(--tw-gradient-stops)); }
        .from-blue-50 { --tw-gradient-from: var(--blue-50); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(239, 246, 255, 0)); }
        .to-indigo-100 { --tw-gradient-to: var(--indigo-100); }
        .from-blue-400 { --tw-gradient-from: var(--blue-400); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(96, 165, 250, 0)); }
        .to-cyan-400 { --tw-gradient-to: #22d3ee; }
        .from-green-400 { --tw-gradient-from: #4ade80; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(74, 222, 128, 0)); }
        .to-emerald-400 { --tw-gradient-to: #34d399; }
        .from-orange-400 { --tw-gradient-from: #fb923c; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(251, 146, 60, 0)); }
        .to-amber-400 { --tw-gradient-to: #fbbf24; }
        .from-purple-400 { --tw-gradient-from: #c084fc; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(192, 132, 252, 0)); }
        .to-pink-400 { --tw-gradient-to: #f472b6; }
        .from-indigo-500 { --tw-gradient-from: var(--indigo-500); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(99, 102, 241, 0)); }
        .to-purple-500 { --tw-gradient-to: var(--purple-500); }
        .from-indigo-600 { --tw-gradient-from: var(--indigo-600); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(79, 70, 229, 0)); }
        .to-purple-600 { --tw-gradient-to: var(--purple-600); }
        .from-purple-50 { --tw-gradient-from: var(--purple-50); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(250, 245, 255, 0)); }
        .to-pink-50 { --tw-gradient-to: #fdf2f8; }
        .from-purple-600 { --tw-gradient-from: var(--purple-600); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(147, 51, 234, 0)); }
        .to-pink-600 { --tw-gradient-to: var(--pink-600); }
        .from-slate-900 { --tw-gradient-from: var(--slate-900); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(15, 23, 42, 0)); }
        .via-purple-900 { --tw-gradient-stops: var(--tw-gradient-from), var(--purple-900), var(--tw-gradient-to, rgba(88, 28, 135, 0)); }
        .to-slate-900 { --tw-gradient-to: var(--slate-900); }
        .from-red-600 { --tw-gradient-from: var(--red-600); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(220, 38, 38, 0)); }
        .to-orange-600 { --tw-gradient-to: var(--orange-600); }
        .from-yellow-500 { --tw-gradient-from: var(--yellow-500); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(234, 179, 8, 0)); }
        .to-orange-500 { --tw-gradient-to: var(--orange-500); }
        .from-blue-500 { --tw-gradient-from: var(--blue-500); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(59, 130, 246, 0)); }
        .to-indigo-500 { --tw-gradient-to: var(--indigo-500); }
        .from-emerald-600 { --tw-gradient-from: var(--emerald-600); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(5, 150, 105, 0)); }
        .to-teal-600 { --tw-gradient-to: var(--teal-600); }
        .from-yellow-500\/20 { --tw-gradient-from: rgba(234, 179, 8, 0.2); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(234, 179, 8, 0)); }
        .to-orange-500\/20 { --tw-gradient-to: rgba(247, 147, 22, 0.2); }

        /* 투명도 */
        .bg-opacity-20 { --tw-bg-opacity: 0.2; }
        .bg-opacity-30 { --tw-bg-opacity: 0.3; }
        .bg-black { background-color: #000000; }
        .bg-opacity-50 { --tw-bg-opacity: 0.5; }

        /* 테두리 */
        .border { border-width: 1px; }
        .border-2 { border-width: 2px; }
        .border-t { border-top-width: 1px; }
        .border-b { border-bottom-width: 1px; }
        .border-gray-100 { border-color: var(--gray-100); }
        .border-gray-200 { border-color: var(--gray-200); }
        .border-indigo-200 { border-color: var(--indigo-200); }
        .border-indigo-300 { border-color: #a5b4fc; }
        .border-indigo-500 { border-color: var(--indigo-500); }
        .border-green-200 { border-color: var(--green-200); }
        .border-red-200 { border-color: #fecaca; }
        .border-white\/10 { border-color: rgba(255, 255, 255, 0.1); }
        .border-white\/20 { border-color: rgba(255, 255, 255, 0.2); }
        .border-yellow-500\/30 { border-color: rgba(234, 179, 8, 0.3); }
        .border-dashed { border-style: dashed; }

        /* 둥근 모서리 */
        .rounded { border-radius: 0.25rem; }
        .rounded-lg { border-radius: 0.5rem; }
        .rounded-xl { border-radius: 0.75rem; }
        .rounded-2xl { border-radius: 1rem; }
        .rounded-3xl { border-radius: 1.5rem; }
        .rounded-full { border-radius: 9999px; }
        .rounded-tl-none { border-top-left-radius: 0px; }
        .rounded-tr-none { border-top-right-radius: 0px; }
        .rounded-sm { border-radius: 0.125rem; }

        /* 텍스트 */
        .text-xs { font-size: 0.75rem; line-height: 1rem; }
        .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
        .text-lg { font-size: 1.125rem; line-height: 1.75rem; }
        .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
        .text-2xl { font-size: 1.5rem; line-height: 2rem; }
        .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
        .text-4xl { font-size: 2.25rem; line-height: 2.5rem; }
        .text-5xl { font-size: 3rem; line-height: 1; }
        .text-6xl { font-size: 3.75rem; line-height: 1; }
        .text-7xl { font-size: 4.5rem; line-height: 1; }
        .text-8xl { font-size: 6rem; line-height: 1; }
        .font-medium { font-weight: 500; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .leading-relaxed { line-height: 1.625; }
        .whitespace-pre-line { white-space: pre-line; }
        .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* 텍스트 색상 */
        .text-white { color: #ffffff; }
        .text-gray-400 { color: var(--gray-400); }
        .text-gray-500 { color: var(--gray-500); }
        .text-gray-600 { color: var(--gray-600); }
        .text-gray-700 { color: var(--gray-700); }
        .text-gray-800 { color: var(--gray-800); }
        .text-blue-600 { color: var(--blue-600); }
        .text-blue-700 { color: #1d4ed8; }
        .text-indigo-600 { color: var(--indigo-600); }
        .text-indigo-700 { color: var(--indigo-700); }
        .text-indigo-800 { color: var(--indigo-800); }
        .text-purple-200 { color: var(--purple-200); }
        .text-purple-300 { color: #d8b4fe; }
        .text-purple-600 { color: var(--purple-600); }
        .text-purple-700 { color: var(--purple-700); }
        .text-green-500 { color: var(--green-500); }
        .text-green-600 { color: var(--green-600); }
        .text-green-700 { color: var(--green-700); }
        .text-green-800 { color: #166534; }
        .text-red-500 { color: var(--red-500); }
        .text-red-600 { color: var(--red-600); }
        .text-red-800 { color: #991b1b; }
        .text-yellow-300 { color: var(--yellow-300); }
        .text-yellow-200 { color: #fef08a; }
        .text-blue-500 { color: var(--blue-500); }
        .text-indigo-100 { color: var(--indigo-100); }
        .text-purple-800 { color: var(--purple-800); }

        /* 그림자 */
        .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        .shadow-xl { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        .shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }

        /* 변환 */
        .transform { transform: translateVar(--tw-translate-x) translateY(var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y)); }
        .scale-105 { --tw-scale-x: 1.05; --tw-scale-y: 1.05; }
        .-translate-x-1\/2 { --tw-translate-x: -50%; }

        /* 전환 */
        .transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
        .transition-colors { transition-property: color, background-color, border-color, text-decoration-color, fill, stroke; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
        .duration-300 { transition-duration: 300ms; }
        .duration-500 { transition-duration: 500ms; }

        /* 호버 */
        .hover\:scale-105:hover { --tw-scale-x: 1.05; --tw-scale-y: 1.05; }
        .hover\:scale-110:hover { --tw-scale-x: 1.1; --tw-scale-y: 1.1; }
        .hover\:bg-gray-50:hover { background-color: var(--gray-50); }
        .hover\:bg-gray-100:hover { background-color: var(--gray-100); }
        .hover\:bg-gray-200:hover { background-color: var(--gray-200); }
        .hover\:bg-gray-300:hover { background-color: var(--gray-300); }
        .hover\:bg-blue-100:hover { background-color: var(--blue-100); }
        .hover\:bg-blue-200:hover { background-color: #c3ddfd; }
        .hover\:bg-green-50:hover { background-color: var(--green-50); }
        .hover\:bg-indigo-100:hover { background-color: var(--indigo-100); }
        .hover\:bg-indigo-200:hover { background-color: var(--indigo-200); }
        .hover\:bg-indigo-700:hover { background-color: var(--indigo-700); }
        .hover\:bg-purple-200:hover { background-color: var(--purple-200); }
        .hover\:bg-white\/10:hover { background-color: rgba(255, 255, 255, 0.1); }
        .hover\:bg-white\/20:hover { background-color: rgba(255, 255, 255, 0.2); }
        .hover\:border-indigo-300:hover { border-color: #a5b4fc; }
        .hover\:border-indigo-400:hover { border-color: #818cf8; }
        .hover\:border-emerald-400:hover { border-color: #34d399; }
        .hover\:text-gray-700:hover { color: var(--gray-700); }
        .hover\:text-gray-800:hover { color: var(--gray-800); }
        .hover\:text-indigo-700:hover { color: var(--indigo-700); }
        .hover\:text-green-700:hover { color: var(--green-700); }
        .hover\:text-yellow-100:hover { color: #fef3c7; }
        .hover\:shadow-xl:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        .group:hover .group-hover\:animate-bounce { animation: bounce 2s infinite; }
        .group:hover .group-hover\:animate-pulse { animation: pulse 2s infinite; }

        /* 포커스 */
        .focus\:border-indigo-500:focus { border-color: var(--indigo-500); }
        .focus\:border-emerald-500:focus { border-color: var(--emerald-600); }
        .focus\:outline-none:focus { outline: 2px solid transparent; outline-offset: 2px; }

        /* 비활성화 */
        .disabled\:opacity-50:disabled { opacity: 0.5; }
        .disabled\:cursor-not-allowed:disabled { cursor: not-allowed; }

        /* 오버플로우 */
        .overflow-hidden { overflow: hidden; }
        .overflow-y-auto { overflow-y: auto; }
        .overflow-x-auto { overflow-x: auto; }

        /* 최소 너비 */
        .min-w-0 { min-width: 0px; }

        /* 크기 조절 */
        .resize-none { resize: none; }

        /* 스크롤 */
        .snap-container { scroll-snap-type: y mandatory; }
        .snap-center { scroll-snap-align: center; }

        /* 커서 */
        .cursor-pointer { cursor: pointer; }
        .cursor-not-allowed { cursor: not-allowed; }

        /* 사용자 정의 애니메이션 */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% { transform: translateY(0); }
            40%, 43% { transform: translateY(-30px); }
            70% { transform: translateY(-15px); }
            90% { transform: translateY(-4px); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .animate-fadeIn { animation: fadeIn 0.8s ease-out; }
        .animate-slideDown { animation: slideDown 0.5s ease-out; }
        .animate-bounce { animation: bounce 2s infinite; }
        .animate-pulse { animation: pulse 2s infinite; }

        /* 유틸리티 */
        .hidden { display: none; }
        .block { display: block; }
        .sticky { position: sticky; }
        .backdrop-blur { backdrop-filter: blur(10px); }
        .backdrop-blur-lg { backdrop-filter: blur(16px); }
        
        /* Glass Effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Additional position utilities */
        .top-16 { top: 4rem; }

        /* 토스트 */
        .toast {
            position: fixed;
            top: 5rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        /* 미디어 쿼리 */
        @media (min-width: 640px) {
            .sm\:block { display: block; }
            .sm\:hidden { display: none; }
        }

        @media (min-width: 768px) {
            .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .md\:inline { display: inline; }
        }

        @media (min-width: 1024px) {
            .lg\:max-w-md { max-width: 28rem; }
        }

        /* 스크롤 동작 */
        html { scroll-behavior: smooth; }

        /* 선택 스타일 */
        ::selection {
            background-color: var(--indigo-500);
            color: white;
        }

        /* 입력 필드 스타일 */
        input, textarea, button {
            font-family: inherit;
        }

        input[type="text"], input[type="date"], textarea {
            appearance: none;
            -webkit-appearance: none;
        }

        /* 버튼 기본 스타일 제거 */
        button {
            background: none;
            border: none;
            cursor: pointer;
        }

        /* 접근성 */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* 상태별 배경색 적용 */
        .bg-white\/5 { background-color: rgba(255, 255, 255, 0.05); }
        .bg-white\/10 { background-color: rgba(255, 255, 255, 0.1); }
        .bg-white\/20 { background-color: rgba(255, 255, 255, 0.2); }
        .bg-blue-500 { background-color: var(--blue-500); }
        .bg-indigo-500 { background-color: var(--indigo-500); }
        .bg-purple-500 { background-color: var(--purple-500); }
        .bg-purple-500\/20 { background-color: rgba(168, 85, 247, 0.2); }
        .bg-purple-500\/50 { background-color: rgba(168, 85, 247, 0.5); }

        /* 그룹 호버 효과를 위한 클래스 */
        .group { position: relative; }

        /* 플렉스 아이템 축소 방지 */
        .flex-shrink-0 { flex-shrink: 0; }

        /* 밑줄 */
        .underline { text-decoration: underline; }

        /* 마지막 자식 제외 */
        .last\:border-0:last-child { border-width: 0px; }

        /* 호버 시 색상 변경용 그라데이션 */
        .hover\:from-indigo-600:hover { --tw-gradient-from: var(--indigo-600); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(79, 70, 229, 0)); }
        .hover\:to-purple-600:hover { --tw-gradient-to: var(--purple-600); }
        .hover\:from-purple-700:hover { --tw-gradient-from: var(--purple-700); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(124, 58, 237, 0)); }
        .hover\:to-pink-700:hover { --tw-gradient-to: #be185d; }
        .hover\:from-yellow-600:hover { --tw-gradient-from: #ca8a04; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(202, 138, 4, 0)); }
        .hover\:to-orange-600:hover { --tw-gradient-to: var(--orange-600); }
        .hover\:from-blue-600:hover { --tw-gradient-from: var(--blue-600); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(37, 99, 235, 0)); }
        .hover\:to-indigo-600:hover { --tw-gradient-to: var(--indigo-600); }
        .hover\:from-emerald-700:hover { --tw-gradient-from: #047857; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(4, 120, 87, 0)); }
        .hover\:to-teal-700:hover { --tw-gradient-to: #0f766e; }
    </style>
</head>
<body class="font-sans">
    <!-- 개발자용 임시 링크 (나중에 제거) -->
    <div class="fixed top-4 right-4 z-50">
        <a href="dashboard.php?userid=<?php echo $userid; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg hover:bg-blue-700 flex items-center gap-2">
            <i class="fas fa-chart-line"></i>
            대시보드 바로가기
        </a>
    </div>
    <!-- 토스트 알림 -->
    <div id="toast" class="toast hidden">
        <div id="toast-content" class="px-6 py-3 rounded-xl shadow-xl border-2 flex items-center gap-2">
            <span id="toast-icon"></span>
            <span id="toast-message" class="font-medium"></span>
        </div>
    </div>

    <!-- 컨테이너 -->
    <div id="container" class="h-screen overflow-y-scroll snap-container bg-gradient-to-b from-blue-50 to-indigo-100 relative">
        
        <!-- 학생 정보 헤더 -->
        <div class="fixed top-0 left-0 right-0 z-50 glass-effect">
            <div class="max-w-6xl mx-auto px-4 py-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-6 text-sm">
                        <span class="text-gray-700 font-medium">
                            <span class="text-gray-500">이름:</span> 
                            <span id="header-student-name"><?php echo $user->firstname . ' ' . $user->lastname; ?></span>
                        </span>
                        <span class="text-gray-700">
                            <span class="text-gray-500">학교:</span> 
                            <span id="header-school"><?php echo $exam_info && $exam_info->school ? $exam_info->school : '-'; ?></span>
                        </span>
                        <span class="text-gray-700">
                            <span class="text-gray-500">학년:</span> 
                            <span id="header-grade"><?php echo $grade ?: ($exam_info && $exam_info->grade ? $exam_info->grade . '학년' : '-'); ?></span>
                        </span>
                        <span class="text-gray-700">
                            <span class="text-gray-500">시험:</span> 
                            <span id="header-exam"><?php 
                                // 자동 계산된 시험 종류 우선 표시
                                $exam_type_map = array(
                                    '1mid' => '1학기 중간고사',
                                    '1final' => '1학기 기말고사',
                                    '2mid' => '2학기 중간고사',
                                    '2final' => '2학기 기말고사'
                                );
                                
                                // 자동 계산된 값이 있으면 사용
                                if ($examType) {
                                    echo isset($exam_type_map[$examType]) ? $exam_type_map[$examType] : '-';
                                } else if ($exam_info && $exam_info->exam_type) {
                                    echo isset($exam_type_map[$exam_info->exam_type]) ? $exam_type_map[$exam_info->exam_type] : $exam_info->exam_type;
                                } else {
                                    echo '-';
                                }
                            ?></span>
                        </span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <span id="current-datetime"></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="fixed top-16 left-0 right-0 z-40 bg-white shadow-md">
            <div class="max-w-6xl mx-auto px-4 py-3">
                <!-- 프로그레스 바 헤더 -->
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700">시험 준비 진행률</span>
                    <span id="progress-percentage" class="text-sm font-medium text-indigo-600">0%</span>
                </div>
                
                <!-- 미니 네비게이션 -->
                <div class="flex items-center justify-between mb-3">
                    <button onclick="goToSection(0)" class="nav-btn flex flex-col items-center gap-1 px-2 py-1 rounded-lg transition-all text-xs bg-indigo-100 text-indigo-700 scale-105">
                        <div class="flex items-center gap-1">
                            <span class="text-lg">🏫</span>
                        </div>
                        <span class="font-medium hidden sm:block">정보입력</span>
                        <span class="font-medium sm:hidden">1</span>
                    </button>
                    <button onclick="goToSection(1)" class="nav-btn flex flex-col items-center gap-1 px-2 py-1 rounded-lg transition-all text-xs text-gray-400 cursor-not-allowed" disabled>
                        <div class="flex items-center gap-1">
                            <span class="text-lg">📝</span>
                        </div>
                        <span class="font-medium hidden sm:block">시험설정</span>
                        <span class="font-medium sm:hidden">2</span>
                    </button>
                    <button onclick="goToSection(2)" class="nav-btn flex flex-col items-center gap-1 px-2 py-1 rounded-lg transition-all text-xs text-gray-400 cursor-not-allowed" disabled>
                        <div class="flex items-center gap-1">
                            <span class="text-lg">🎯</span>
                        </div>
                        <span class="font-medium hidden sm:block">전략이해</span>
                        <span class="font-medium sm:hidden">3</span>
                    </button>
                    <button onclick="goToSection(3)" class="nav-btn flex flex-col items-center gap-1 px-2 py-1 rounded-lg transition-all text-xs text-gray-400 cursor-not-allowed" disabled>
                        <div class="flex items-center gap-1">
                            <span class="text-lg">🚀</span>
                        </div>
                        <span class="font-medium hidden sm:block">단계선택</span>
                        <span class="font-medium sm:hidden">4</span>
                    </button>
                    <button onclick="goToSection(4)" class="nav-btn flex flex-col items-center gap-1 px-2 py-1 rounded-lg transition-all text-xs text-gray-400 cursor-not-allowed" disabled>
                        <div class="flex items-center gap-1">
                            <span class="text-lg">📊</span>
                        </div>
                        <span class="font-medium hidden sm:block">시작하기</span>
                        <span class="font-medium sm:hidden">5</span>
                    </button>
                </div>
                
                <!-- 프로그레스 바 -->
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progress-bar" class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- FAQ Button -->
        <button id="faq-btn" onclick="showFAQ()" class="fixed bottom-8 right-8 z-30 bg-indigo-600 text-white p-4 rounded-full shadow-lg hover:bg-indigo-700 transition-all hover:scale-110 flex items-center gap-2">
            <span class="text-2xl">❓</span>
            <span class="hidden md:inline">도움말</span>
        </button>

        <!-- Section 1: 기본 정보 입력 -->
        <section id="section-0" class="min-h-screen snap-center flex items-center justify-center p-8 pt-20">
            <div class="max-w-3xl w-full">
                <div class="bg-white rounded-3xl shadow-2xl p-10">
                    <div class="text-center mb-8">
                        <div class="text-8xl mb-4 animate-bounce">🏫</div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">
                            <span class="text-indigo-600">시험 준비</span> 시작하기
                        </h1>
                    </div>
                    
                    <!-- 타이핑 효과 가이드 -->
                    <div class="h-8 mb-8">
                        <p id="guide-text-0" class="text-lg text-gray-600 text-center"></p>
                    </div>
                    
                    <div id="section-0-content" class="space-y-6">
                        <!-- 학교 입력 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">학교 이름</label>
                            <input
                                id="school-input"
                                type="text"
                                placeholder="예: 대전고등학교"
                                class="w-full p-4 text-lg border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:outline-none transition-all"
                            />
                        </div>

                        <!-- 학년 선택 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">학년</label>
                            <div id="grade-buttons-container">
                                <!-- 기본 1-3학년 버튼 -->
                                <div class="grid grid-cols-3 gap-4">
                                    <button onclick="selectGrade('1', event)" class="grade-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 transition-all transform hover:scale-105">
                                        <div class="text-2xl mb-1">🌱</div>
                                        <div class="font-medium">1학년</div>
                                    </button>
                                    <button onclick="selectGrade('2', event)" class="grade-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 transition-all transform hover:scale-105">
                                        <div class="text-2xl mb-1">🌿</div>
                                        <div class="font-medium">2학년</div>
                                    </button>
                                    <button onclick="selectGrade('3', event)" class="grade-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 transition-all transform hover:scale-105">
                                        <div class="text-2xl mb-1">🌳</div>
                                        <div class="font-medium">3학년</div>
                                    </button>
                                </div>
                                <!-- 초등학교 4-6학년 버튼 (필요 시 표시) -->
                                <div id="elementary-grades" class="grid grid-cols-3 gap-4 mt-3 hidden">
                                    <button onclick="selectGrade('4', event)" class="grade-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 transition-all transform hover:scale-105">
                                        <div class="text-2xl mb-1">🌼</div>
                                        <div class="font-medium">4학년</div>
                                    </button>
                                    <button onclick="selectGrade('5', event)" class="grade-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 transition-all transform hover:scale-105">
                                        <div class="text-2xl mb-1">🌻</div>
                                        <div class="font-medium">5학년</div>
                                    </button>
                                    <button onclick="selectGrade('6', event)" class="grade-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 transition-all transform hover:scale-105">
                                        <div class="text-2xl mb-1">🌸</div>
                                        <div class="font-medium">6학년</div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 시험 종류 선택 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">시험 종류</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button onclick="selectExamType('1mid', event)" class="exam-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 bg-white transition-all transform hover:scale-105">
                                    <div class="text-2xl mb-1">🌸</div>
                                    <div class="font-medium text-sm">1학기 중간고사</div>
                                </button>
                                <button onclick="selectExamType('1final', event)" class="exam-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 bg-white transition-all transform hover:scale-105">
                                    <div class="text-2xl mb-1">☀️</div>
                                    <div class="font-medium text-sm">1학기 기말고사</div>
                                </button>
                                <button onclick="selectExamType('2mid', event)" class="exam-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 bg-white transition-all transform hover:scale-105">
                                    <div class="text-2xl mb-1">🍂</div>
                                    <div class="font-medium text-sm">2학기 중간고사</div>
                                </button>
                                <button onclick="selectExamType('2final', event)" class="exam-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 bg-white transition-all transform hover:scale-105">
                                    <div class="text-2xl mb-1">❄️</div>
                                    <div class="font-medium text-sm">2학기 기말고사</div>
                                </button>
                            </div>
                        </div>

                        <button 
                            id="next-btn-0"
                            onclick="completeSection(0)"
                            disabled
                            class="w-full py-5 bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-lg font-medium rounded-2xl hover:from-indigo-600 hover:to-purple-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105"
                        >
                            다음으로 →
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: 시험 정보 입력 -->
        <section id="section-1" class="min-h-screen snap-center flex items-center justify-center p-8 pt-20">
            <div class="max-w-4xl w-full">
                <div class="bg-white rounded-3xl shadow-2xl p-10">
                    <div class="text-center mb-8">
                        <div class="text-8xl mb-4">📝</div>
                        <h2 class="text-3xl font-bold text-gray-800">시험 정보 입력</h2>
                    </div>
                    
                    <div class="h-12 mb-6">
                        <p id="guide-text-1" class="text-lg text-gray-600 text-center"></p>
                    </div>

                    <div id="section-1-content" class="space-y-8 hidden">

                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- 시험 기간 -->
                            <div class="space-y-4">
                                <h3 class="font-semibold text-lg flex items-center gap-2">
                                    <span class="text-xl">📅</span>
                                    시험 기간
                                    <span class="ml-4 inline-flex items-center gap-1 text-sm bg-orange-500 text-white px-4 py-2 rounded-lg border-2 border-orange-600 font-bold shadow-lg hover:bg-orange-600 transition-colors">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        확인요망
                                    </span>
                                </h3>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-sm font-medium text-gray-700">시작일</label>
                                        <span id="exam-start-status" class="text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-700">예상</span>
                                    </div>
                                    <input 
                                        id="exam-start"
                                        type="date" 
                                        class="w-full p-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none"
                                        onchange="updateDDay()"
                                    />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-sm font-medium text-gray-700">종료일</label>
                                        <span id="exam-end-status" class="text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-700">예상</span>
                                    </div>
                                    <input 
                                        id="exam-end"
                                        type="date" 
                                        class="w-full p-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none"
                                        onchange="updateDDay()"
                                    />
                                </div>
                            </div>

                            <!-- 수학 시험일 -->
                            <div class="space-y-4">
                                <h3 class="font-semibold text-lg flex items-center gap-2">
                                    <span class="text-xl">🎯</span>
                                    수학 시험일
                                    <span class="ml-4 inline-flex items-center gap-1 text-sm bg-orange-500 text-white px-4 py-2 rounded-lg border-2 border-orange-600 font-bold shadow-lg hover:bg-orange-600 transition-colors">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        확인요망
                                    </span>
                                </h3>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-sm font-medium text-gray-700">수학 시험 날짜</label>
                                        <span id="math-date-status" class="text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-700">예상</span>
                                    </div>
                                    <input 
                                        id="math-date"
                                        type="date" 
                                        class="w-full p-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none"
                                        onchange="updateDDay()"
                                    />
                                </div>
                                <div id="dday-display" class="bg-gradient-to-r from-indigo-100 to-purple-100 rounded-xl p-4 text-center hidden">
                                    <p id="dday-number" class="text-3xl font-bold text-indigo-800">D-?</p>
                                    <p class="text-sm text-indigo-600">수학 시험까지</p>
                                </div>
                            </div>
                        </div>

                        <!-- 시험 범위 -->
                        <div>
                            <h3 class="font-semibold text-lg mb-3 flex items-center gap-2" id="exam-scope-label">
                                <span class="text-xl">📚</span>
                                시험 범위
                            </h3>
                            <textarea 
                                id="exam-scope"
                                class="w-full p-4 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:outline-none h-24 resize-none"
                                placeholder="예: 1단원 ~ 3단원 (도형의 성질까지)"
                                onchange="checkSection1Complete()"
                            ></textarea>
                        </div>

                        <!-- 예상/확정 선택 -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="flex justify-center gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input 
                                        type="radio" 
                                        name="status" 
                                        value="expected" 
                                        checked
                                        class="w-5 h-5"
                                        onchange="updateExamStatus(this.value)"
                                    />
                                    <span class="text-lg">모든 정보 예상 🤔</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input 
                                        type="radio" 
                                        name="status" 
                                        value="confirmed"
                                        class="w-5 h-5"
                                        onchange="updateExamStatus(this.value)"
                                    />
                                    <span class="text-lg">모든 정보 확정 ✅</span>
                                </label>
                            </div>
                        </div>

                        <!-- 같은 학교 친구들 정보 (참고용 표시) -->
                        <div class="bg-blue-50 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4 cursor-pointer" onclick="toggleFriendsInfo()">
                                <h3 class="text-lg font-semibold flex items-center gap-2">
                                    <span class="text-xl">👥</span>
                                    같은 학교 친구들의 시험 정보
                                </h3>
                                <button type="button" class="text-gray-600 hover:text-gray-800 transition-all">
                                    <svg id="friends-toggle-icon" class="w-6 h-6 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <div id="friends-info-container" class="hidden">
                                <!-- 학교 홈페이지 링크 버튼 -->
                                <div class="mb-4">
                                    <button id="school-website-btn" onclick="openSchoolWebsite()" class="w-full py-3 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition-all flex items-center justify-center gap-2 text-blue-600">
                                        <span>🏫</span>
                                        <span>학교 홈페이지 바로가기</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- 친구들 정보 표시 -->
                                <div id="friends-info-display" class="space-y-3">
                                    <div class="text-center text-gray-500 py-4">
                                        <div class="text-3xl mb-2">🔍</div>
                                        <p>같은 시험 정보를 찾는 중...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button 
                            id="next-btn-1"
                            onclick="completeSection(1)"
                            disabled
                            class="w-full py-5 bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-lg font-medium rounded-2xl hover:from-indigo-600 hover:to-purple-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            다음으로 →
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: 라스트 청킹 안내 -->
        <section id="section-2" class="min-h-screen snap-center flex items-center justify-center p-8 pt-20">
            <div class="max-w-3xl w-full">
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-3xl shadow-2xl p-10">
                    <div class="text-center mb-8">
                        <div class="text-8xl mb-4">🎯</div>
                        <h2 class="text-3xl font-bold text-gray-800">라스트 청킹</h2>
                        <p class="text-gray-600 mt-2">시험 직전 3-5일의 마법</p>
                    </div>

                    <div class="h-12 mb-6">
                        <p id="guide-text-2" class="text-lg text-gray-700 text-center"></p>
                    </div>

                    <div id="section-2-content" class="space-y-8 hidden">
                        <!-- 간단한 핵심 설명 -->
                        <div class="bg-white rounded-2xl p-8 shadow-lg text-center">
                            <div class="text-7xl mb-4">🧠</div>
                            <h3 class="font-bold text-2xl text-purple-800 mb-4">시험 3-5일 전 가벼운 복습</h3>
                            <p class="text-gray-700 text-lg mb-6">
                                시험 전 마지막 5일, 성적을 폭발시키는 비밀 무기! <span class="font-bold text-purple-600">💥</span>
                            </p>
                            
                            <!-- 간단한 효과 -->
                            <div class="flex justify-center gap-8 text-sm text-gray-600 mb-6">
                                <span>🎯 컨디션 향상</span>
                                <span>⚡ 빠른 문제 해결</span>
                                <span>🔥 기억력 강화</span>
                            </div>

                            <!-- 자세한 설명 버튼 - 서버 페이지로 이동 -->
                            <button 
                                onclick="window.location.href='last_chunking.php?userid=<?php echo $userid; ?>'"
                                class="px-6 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-all text-sm flex items-center justify-center gap-2 mx-auto"
                            >
                                자세한 설명 보기 <span class="text-sm">🚀</span>
                            </button>
                        </div>

                        <button 
                            onclick="completeSection(2)"
                            class="w-full py-5 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-lg font-medium rounded-2xl hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105"
                        >
                            이해했어! 다음으로 →
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 4: 학습 시작점 선택 -->
        <section id="section-3" class="min-h-screen snap-center flex items-center justify-center p-8 pt-20">
            <div class="max-w-3xl w-full">
                <div class="bg-white rounded-3xl shadow-2xl p-10">
                    <div class="text-center mb-8">
                        <div class="text-8xl mb-4">🚀</div>
                        <h2 class="text-3xl font-bold text-gray-800">너의 시작 위치는?</h2>
                    </div>

                    <div class="h-12 mb-6">
                        <p id="guide-text-3" class="text-lg text-gray-600 text-center"></p>
                    </div>

                    <div id="section-3-content" class="space-y-6 max-h-96 overflow-y-auto hidden">
                        <div class="phase-btn w-full p-6 rounded-2xl border-2 border-gray-200 hover:border-indigo-300 transition-all" onclick="selectPhase('concept')">
                            <div class="w-full flex items-center gap-4 transform hover:scale-105 transition-transform cursor-pointer">
                                <div class="p-4 rounded-xl bg-blue-500 bg-opacity-20">
                                    <span class="text-blue-500 text-4xl">📚</span>
                                </div>
                                <div class="text-left flex-1">
                                    <div class="font-bold text-xl">개념공부</div>
                                    <div class="text-sm text-gray-600 mt-1">기본 개념부터 차근차근 시작해요</div>
                                </div>
                            </div>
                            
                            <!-- 상세 설명 -->
                            <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                                <h4 class="font-semibold text-gray-800 mb-2">📋 상세 가이드</h4>
                                <p class="text-sm text-gray-700 leading-relaxed">
                                    시험 기간이라면 완벽한 개념 학습보다는 핵심 개념을 빠르게 정리하는 것이 중요해요. 기본 개념만 확실히 잡고 문제 풀이로 넘어가세요.
                                </p>
                            </div>
                        </div>

                        <div class="phase-btn w-full p-6 rounded-2xl border-2 border-gray-200 hover:border-indigo-300 transition-all" onclick="selectPhase('concept-review')">
                            <div class="w-full flex items-center gap-4 transform hover:scale-105 transition-transform cursor-pointer">
                                <div class="p-4 rounded-xl bg-indigo-500 bg-opacity-20">
                                    <span class="text-indigo-500 text-4xl">🧠</span>
                                </div>
                                <div class="text-left flex-1">
                                    <div class="font-bold text-xl">개념복습</div>
                                    <div class="text-sm text-gray-600 mt-1">배운 개념들을 다시 정리하고 복습해요</div>
                                </div>
                            </div>
                            
                            <!-- 상세 설명 -->
                            <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                                <h4 class="font-semibold text-gray-800 mb-2">📋 상세 가이드</h4>
                                <p class="text-sm text-gray-700 leading-relaxed">
                                    이미 배운 개념들을 빠르게 복습하는 단계예요.<br><br>
                                    • 기억이 안 나면: 개념 다시 정리<br>
                                    • 어느 정도 기억나면: 유형 테스트 3회 → 단원 테스트 90점<br>
                                    • 개념이 잡혀있으면: 바로 단원 테스트 90점 도전
                                </p>
                            </div>
                        </div>

                        <div class="phase-btn w-full p-6 rounded-2xl border-2 border-gray-200 hover:border-indigo-300 transition-all" onclick="selectPhase('type-study')">
                            <div class="w-full flex items-center gap-4 transform hover:scale-105 transition-transform cursor-pointer">
                                <div class="p-4 rounded-xl bg-purple-500 bg-opacity-20">
                                    <span class="text-purple-500 text-4xl">✏️</span>
                                </div>
                                <div class="text-left flex-1">
                                    <div class="font-bold text-xl">유형공부</div>
                                    <div class="text-sm text-gray-600 mt-1">다양한 문제 유형들을 학습해요</div>
                                </div>
                            </div>
                            
                            <!-- 상세 설명 -->
                            <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                                <h4 class="font-semibold text-gray-800 mb-2">📋 상세 가이드</h4>
                                <p class="text-sm text-gray-700 leading-relaxed">
                                    mathking 내신테스트로 시작해서 중급→심화 유형 순서로 학습하세요. 교재 문제와 병행하며 마지막에 기출문제까지 완주!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 5: 최종 대시보드 -->
        <section id="section-4" class="min-h-screen snap-center bg-gradient-to-b from-gray-50 to-gray-100">
            <div class="min-h-screen">
                <!-- 미니멀 헤더 -->
                <div class="p-6 pb-0">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center gap-4">
                            <h1 class="text-2xl font-light text-gray-800">대시보드</h1>
                            <!-- 모드 스위치 -->
                            <div class="flex bg-gray-200 rounded-lg p-1">
                                <button id="scroll-mode-btn" type="button" class="px-3 py-1 text-sm rounded bg-white text-gray-800 shadow-sm transition-all cursor-pointer">스크롤</button>
                                <button id="tab-mode-btn" type="button" class="px-3 py-1 text-sm rounded text-gray-600 hover:text-gray-800 transition-all cursor-pointer">탭</button>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p id="current-time" class="text-xl font-light text-gray-800"></p>
                                <p class="text-xs text-gray-500">Focus Mode</p>
                            </div>
                            <button onclick="showNotifications()" class="relative p-2 text-gray-600 hover:text-gray-800 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                <span id="notification-badge" class="notification-badge hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">0</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 스크롤 모드 컨테이너 -->
                <div id="scroll-mode-container" class="">
                    <!-- 모드 설명 -->
                    <div class="px-4 lg:px-6 pt-2 pb-0">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-3 lg:p-4 mb-4 border border-blue-200">
                            <div class="flex items-start gap-2 lg:gap-3">
                                <span class="text-lg lg:text-2xl">📜</span>
                                <div>
                                    <h3 class="font-bold text-blue-800 mb-1 text-sm lg:text-base">스크롤 모드: 전체 현황 파악</h3>
                                    <p class="text-xs lg:text-sm text-blue-700 leading-relaxed">
                                        모든 학습 정보를 한 페이지에서 종합적으로 확인 • 진행도부터 목표까지 전체 상황을 빠르게 파악 • 전반적인 학습 상태를 한눈에 비교
                                    </p>
                                    <div class="flex gap-2 lg:gap-4 mt-2 text-xs text-blue-600">
                                        <span>✓ 종합적 시각</span>
                                        <span>✓ 빠른 비교</span>
                                        <span class="hidden sm:inline">✓ 전체 맥락</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 스크롤 모드: 모든 정보를 한 페이지에서 스크롤하며 볼 수 있음 -->
                    <div class="px-4 lg:px-6 py-4 overflow-y-auto" style="max-height: calc(100vh - 200px);">
                        <!-- 상단 요약 카드들 - 반응형 그리드 -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6">
                            <!-- D-Day 카드 -->
                            <div class="bg-gradient-to-r from-red-500 to-pink-600 rounded-xl p-4 lg:p-6 text-center text-white shadow-lg">
                                <p class="text-xs lg:text-sm mb-2 opacity-90">시험까지</p>
                                <p id="dashboard-dday" class="text-2xl lg:text-3xl font-bold">D-?</p>
                            </div>
                            <!-- 학교 정보 -->
                            <div class="bg-white rounded-xl p-4 lg:p-6 shadow-lg">
                                <p class="text-xs lg:text-sm text-gray-600 mb-2">학교 · 학년</p>
                                <p id="profile-school" class="text-sm lg:text-lg text-gray-800 font-medium truncate">-</p>
                                <p id="profile-grade" class="text-xs lg:text-sm text-gray-600">-</p>
                            </div>
                            <!-- 시험 종류 -->
                            <div class="bg-white rounded-xl p-4 lg:p-6 shadow-lg">
                                <p class="text-xs lg:text-sm text-gray-600 mb-2">시험 정보</p>
                                <p id="profile-exam" class="text-sm lg:text-lg text-gray-800 font-medium truncate">-</p>
                                <p class="text-xs lg:text-sm text-gray-600">
                                    <span id="exam-start-display">-</span> ~ <span id="exam-end-display">-</span>
                                </p>
                            </div>
                            <!-- 오늘 학습 -->
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-4 lg:p-6 text-white shadow-lg">
                                <p class="text-xs lg:text-sm mb-2 opacity-90">오늘 학습</p>
                                <p id="study-time" class="text-sm lg:text-lg font-medium">0시간 0분</p>
                                <p class="text-xs lg:text-sm opacity-90"><span id="completed-activities">0</span>개 완료</p>
                            </div>
                        </div>
                    </div>

                        <!-- 스크롤 모드 메인 콘텐츠 -->
                        <div class="space-y-4 lg:space-y-6">
                            <!-- 시험 정보 섹션 -->
                            <div class="bg-white rounded-xl p-4 lg:p-6 shadow-lg border border-gray-100">
                                <h2 class="text-lg lg:text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                    <span class="text-xl lg:text-2xl">📝</span> 
                                    <span>시험 정보</span>
                                </h2>
                                <div class="space-y-4 lg:grid lg:grid-cols-2 lg:gap-6 lg:space-y-0">
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-sm text-gray-600 mb-2 font-medium">📚 시험 범위</p>
                                        <p id="dashboard-scope-scroll" class="text-gray-800 text-sm lg:text-base leading-relaxed">범위 미입력</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-sm text-gray-600 mb-2 font-medium">📅 시험 기간</p>
                                        <p class="text-gray-800 text-sm lg:text-base font-medium">
                                            <span id="exam-start-display-scroll">-</span> ~ <span id="exam-end-display-scroll">-</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                        <!-- 학습 진행 현황 -->
                        <div class="bg-white rounded-xl p-6 shadow-md">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <span>📊</span> 학습 진행 현황
                            </h2>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">📚 개념공부</span>
                                        <span class="text-sm text-gray-600">0%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full bg-blue-500" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">🧠 개념복습</span>
                                        <span class="text-sm text-gray-600">20%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full bg-indigo-500" style="width: 20%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">✏️ 유형공부</span>
                                        <span class="text-sm text-gray-600">40%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full bg-purple-500" style="width: 40%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- LMS 정보 통합 -->
                        <div class="bg-white rounded-xl p-6 shadow-md">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <span>🎓</span> LMS 학습 정보
                            </h2>
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-700 mb-3">기본 정보</h3>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">학원</span>
                                            <span id="lms-academy-scroll" class="text-gray-800 font-medium">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">지역</span>
                                            <span id="lms-location-scroll" class="text-gray-800 font-medium">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">학습모드</span>
                                            <span id="lms-lmode-scroll" class="text-gray-800 font-medium">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-700 mb-3">학습 스타일</h3>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">평가</span>
                                            <span id="lms-evaluate-scroll" class="text-gray-800 font-medium">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">커리큘럼</span>
                                            <span id="lms-curriculum-scroll" class="text-gray-800 font-medium">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">목표 안정도</span>
                                            <span id="lms-goalstability-scroll" class="text-gray-800 font-medium">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-700 mb-3">PRESET 설정</h3>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">개념미션</span>
                                            <span id="lms-preset-concept-scroll" class="text-gray-800 font-medium">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">심화미션</span>
                                            <span id="lms-preset-advanced-scroll" class="text-gray-800 font-medium">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">내신미션</span>
                                            <span id="lms-preset-school-scroll" class="text-gray-800 font-medium">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 오늘의 목표 -->
                        <div class="bg-white rounded-xl p-6 shadow-md">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <span>🎯</span> 목표 관리
                            </h2>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-300">
                                    <p class="text-sm text-yellow-700 mb-2">💪 오늘의 목표</p>
                                    <p id="daily-goal-scroll" class="text-gray-800 font-medium">로딩 중...</p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-4 border border-green-300">
                                    <p class="text-sm text-green-700 mb-2">📅 주간 목표</p>
                                    <p id="weekly-goal-scroll" class="text-gray-800 font-medium">로딩 중...</p>
                                </div>
                                <div class="bg-purple-50 rounded-lg p-4 border border-purple-300">
                                    <p class="text-sm text-purple-700 mb-2">🎯 분기 목표</p>
                                    <p id="quarter-goal-scroll" class="text-gray-800 font-medium">로딩 중...</p>
                                </div>
                            </div>
                        </div>

                        <!-- 학습 통계 -->
                        <div class="bg-white rounded-xl p-6 shadow-md">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <span>📈</span> 학습 통계
                            </h2>
                            <div class="grid grid-cols-4 gap-4 text-center">
                                <div>
                                    <p class="text-3xl font-bold text-gray-800">24h</p>
                                    <p class="text-sm text-gray-600 mt-1">총 학습시간</p>
                                </div>
                                <div>
                                    <p class="text-3xl font-bold text-gray-800">156</p>
                                    <p class="text-sm text-gray-600 mt-1">완료 문제</p>
                                </div>
                                <div>
                                    <p class="text-3xl font-bold text-gray-800">78%</p>
                                    <p class="text-sm text-gray-600 mt-1">평균 정답률</p>
                                </div>
                                <div>
                                    <p class="text-3xl font-bold text-gray-800">7일</p>
                                    <p class="text-sm text-gray-600 mt-1">연속 학습</p>
                                </div>
                            </div>
                            <!-- 빠른 실행 버튼들 (스크롤 모드 전용) -->
                            <div class="bg-white rounded-xl p-6 shadow-md">
                                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                    <span>⚡</span> 빠른 실행
                                </h2>
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                    <button class="bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl p-4 hover:from-purple-700 hover:to-pink-700 transition-all flex flex-col items-center gap-2 shadow-lg">
                                        <span class="text-2xl">⚡</span>
                                        <span class="font-medium text-sm">학습 시작</span>
                                    </button>
                                    <button onclick="showExamInfo()" class="bg-blue-500 hover:bg-blue-600 text-white rounded-xl p-4 transition-all flex flex-col items-center gap-2 shadow-lg">
                                        <span class="text-2xl">📄</span>
                                        <span class="font-medium text-sm">시험 정보</span>
                                    </button>
                                    <button onclick="openAIChat()" class="bg-purple-500 hover:bg-purple-600 text-white rounded-xl p-4 transition-all flex flex-col items-center gap-2 shadow-lg">
                                        <span class="text-2xl">🤖</span>
                                        <span class="font-medium text-sm">AI 튜터</span>
                                    </button>
                                    <button onclick="showUpload()" class="bg-green-500 hover:bg-green-600 text-white rounded-xl p-4 transition-all flex flex-col items-center gap-2 shadow-lg">
                                        <span class="text-2xl">📤</span>
                                        <span class="font-medium text-sm">자료 업로드</span>
                                    </button>
                                </div>
                            </div>

                            <!-- 수학일기 전용 버튼 (스크롤 모드) -->
                            <div class="bg-white rounded-xl p-6 shadow-md">
                                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                    <span>📒</span> 수학일기
                                </h2>
                                <button onclick="window.open(`info_time.php?userid=${userData.userid}`, '_blank')" class="w-full bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-xl p-6 hover:from-cyan-600 hover:to-teal-600 transition-all flex flex-col items-center gap-3 shadow-lg">
                                    <span class="text-3xl">📒</span>
                                    <span class="font-medium">오늘의 학습</span>
                                    <span class="text-sm opacity-90">학습 기록을 작성하세요</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 탭 모드 컨테이너 -->
                <div id="tab-mode-container" class="hidden px-3 md:px-4 lg:px-6 pb-20">
                    <!-- 모드 설명 -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-3 lg:p-4 mb-4 border border-purple-200">
                        <div class="flex items-start gap-2 lg:gap-3">
                            <span class="text-lg lg:text-2xl">🎯</span>
                            <div>
                                <h3 class="font-bold text-purple-800 mb-1 text-sm lg:text-base">탭 모드: 집중적 학습 관리</h3>
                                <p class="text-xs lg:text-sm text-purple-700 leading-relaxed">
                                    필요한 정보만 선택적으로 확인하여 집중력 극대화 • 현황-진행도-목표-통계를 체계적으로 분리 • 각 영역에 깊이 있게 집중
                                </p>
                                <div class="flex gap-2 lg:gap-4 mt-2 text-xs text-purple-600">
                                    <span>✓ 선택적 집중</span>
                                    <span>✓ 체계적 분류</span>
                                    <span class="hidden sm:inline">✓ 깊이 있는 분석</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 탭 네비게이션 -->
                    <div class="flex gap-2 mb-6 border-b border-gray-300">
                                <button type="button" onclick="selectTab('overview')" class="tab-btn px-4 py-2 text-sm font-medium text-gray-800 border-b-2 border-purple-500 transition-all">현황</button>
                                <button type="button" onclick="selectTab('progress')" class="tab-btn px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-all">진행도</button>
                                <button type="button" onclick="selectTab('goals')" class="tab-btn px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-all">목표</button>
                                <button type="button" onclick="selectTab('stats')" class="tab-btn px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-all">통계</button>
                            </div>
                            
                            <!-- 탭 콘텐츠 -->
                            <div id="tab-content">
                                <!-- 현황 탭 -->
                                <div id="tab-overview" class="tab-panel">
                                    <div class="flex flex-col lg:grid lg:grid-cols-12 gap-4 lg:gap-6">
                                        <!-- 왼쪽: 핵심 정보 -->
                                        <div class="lg:col-span-8 space-y-4 lg:space-y-6">
                                            <!-- 시험 정보 카드 -->
                                            <div class="bg-white rounded-xl p-4 lg:p-6 shadow-md">
                                                <h3 class="text-base lg:text-lg font-medium text-gray-800 mb-3 lg:mb-4">시험 정보</h3>
                                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 lg:gap-4">
                                                    <div>
                                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">학교</p>
                                                        <p class="text-sm lg:text-base text-gray-800 font-medium truncate" id="tab-school">-</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">학년</p>
                                                        <p class="text-sm lg:text-base text-gray-800 font-medium" id="tab-grade">-</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">시험</p>
                                                        <p class="text-sm lg:text-base text-gray-800 font-medium truncate" id="tab-exam">-</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">기간</p>
                                                        <p class="text-xs lg:text-sm text-gray-800" id="tab-period">-</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">D-Day</p>
                                                        <p class="text-red-600 text-base lg:text-lg font-bold" id="tab-dday">D-?</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">상태</p>
                                                        <p>
                                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">예상</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="mt-3 lg:mt-4 pt-3 lg:pt-4 border-t border-gray-200">
                                                    <p class="text-xs lg:text-sm text-gray-600 mb-2">시험 범위</p>
                                                    <p class="text-sm lg:text-base text-gray-800" id="tab-scope">범위 미입력</p>
                                                </div>
                                            </div>
                                            
                                            <!-- 오늘의 학습 -->
                                            <div class="bg-white rounded-xl p-4 lg:p-6 shadow-md">
                                                <h3 class="text-base lg:text-lg font-medium text-gray-800 mb-3 lg:mb-4">오늘의 학습</h3>
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 lg:gap-4 text-center">
                                                    <div>
                                                        <p class="text-lg lg:text-2xl font-bold text-gray-800">0시간</p>
                                                        <p class="text-xs text-gray-600 mt-1">학습 시간</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-lg lg:text-2xl font-bold text-gray-800">0개</p>
                                                        <p class="text-xs text-gray-600 mt-1">완료 활동</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-lg lg:text-2xl font-bold text-gray-800">0%</p>
                                                        <p class="text-xs text-gray-600 mt-1">정답률</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-lg lg:text-2xl font-bold text-gray-800">⭐⭐⭐</p>
                                                        <p class="text-xs text-gray-600 mt-1">만족도</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- 오른쪽: 빠른 액션 (탭 모드 최적화) -->
                                        <div class="lg:col-span-4 space-y-3">
                                            <!-- 주요 액션 (크게) -->
                                            <button onclick="window.location.href='dashboard.php?userid=<?php echo $userid; ?>'" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg p-3 lg:p-4 hover:from-purple-700 hover:to-pink-700 transition-all flex items-center justify-center gap-2 lg:gap-3 shadow-md">
                                                <span class="text-lg lg:text-2xl">⚡</span>
                                                <span class="font-medium text-sm lg:text-base">학습 대시보드</span>
                                            </button>
                                            
                                            <!-- 서브 액션들 (모바일에서 4컬럼, 태블릿+에서 2x2 그리드) -->
                                            <div class="grid grid-cols-4 lg:grid-cols-2 gap-1 lg:gap-2">
                                                <button onclick="showExamInfo()" class="bg-blue-500 hover:bg-blue-600 text-white rounded-md p-2 lg:p-3 text-xs font-medium transition-all flex flex-col lg:flex-row items-center gap-1 lg:gap-2 shadow-sm">
                                                    <span class="text-sm">📄</span>
                                                    <span class="hidden lg:inline">시험 정보</span>
                                                    <span class="lg:hidden text-xs">시험</span>
                                                </button>
                                                <button onclick="openAIChat()" class="bg-purple-500 hover:bg-purple-600 text-white rounded-md p-2 lg:p-3 text-xs font-medium transition-all flex flex-col lg:flex-row items-center gap-1 lg:gap-2 shadow-sm">
                                                    <span class="text-sm">🤖</span>
                                                    <span class="hidden lg:inline">AI 튜터</span>
                                                    <span class="lg:hidden text-xs">AI</span>
                                                </button>
                                                <button onclick="showUpload()" class="bg-green-500 hover:bg-green-600 text-white rounded-md p-2 lg:p-3 text-xs font-medium transition-all flex flex-col lg:flex-row items-center gap-1 lg:gap-2 shadow-sm">
                                                    <span class="text-sm">📤</span>
                                                    <span class="hidden lg:inline">자료 업로드</span>
                                                    <span class="lg:hidden text-xs">업로드</span>
                                                </button>
                                                <button onclick="window.open(`info_time.php?userid=${userData.userid}`, '_blank')" class="bg-cyan-500 hover:bg-cyan-600 text-white rounded-md p-2 lg:p-3 text-xs font-medium transition-all flex flex-col lg:flex-row items-center gap-1 lg:gap-2 shadow-sm">
                                                    <span class="text-sm">📒</span>
                                                    <span class="hidden lg:inline">수학일기</span>
                                                    <span class="lg:hidden text-xs">일기</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 진행도 탭 -->
                                <div id="tab-progress" class="tab-panel hidden">
                                    <div class="bg-white rounded-xl p-6 shadow-md">
                                        <h3 class="text-lg font-medium text-gray-800 mb-4">학습 진행 현황</h3>
                                        <div class="space-y-4">
                                            <div class="phase-progress">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-sm font-medium text-gray-700">📚 개념공부</span>
                                                    <span class="text-sm text-gray-600">0%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 rounded-full bg-blue-500" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <div class="phase-progress">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-sm font-medium text-gray-700">🧠 개념복습</span>
                                                    <span class="text-sm text-gray-600">20%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 rounded-full bg-indigo-500" style="width: 20%"></div>
                                                </div>
                                            </div>
                                            <div class="phase-progress">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-sm font-medium text-gray-700">✏️ 유형공부</span>
                                                    <span class="text-sm text-gray-600">40%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 rounded-full bg-purple-500" style="width: 40%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 목표 탭 -->
                                <div id="tab-goals" class="tab-panel hidden">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div class="bg-yellow-50 rounded-xl p-6 border border-yellow-300">
                                            <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center gap-2">
                                                <span>💪</span> 오늘의 목표
                                            </h3>
                                            <p class="text-gray-700">유형공부 3단원 완료하기</p>
                                        </div>
                                        <div class="bg-green-50 rounded-xl p-6 border border-green-300">
                                            <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center gap-2">
                                                <span>📅</span> 주간 목표
                                            </h3>
                                            <p class="text-gray-700">전체 유형 문제 50% 완료</p>
                                        </div>
                                        <div class="bg-purple-50 rounded-xl p-6 border border-purple-300">
                                            <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center gap-2">
                                                <span>🎯</span> 분기 목표
                                            </h3>
                                            <p class="text-gray-700">수학 성적 90점 이상</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 통계 탭 -->
                                <div id="tab-stats" class="tab-panel hidden">
                                    <div class="grid grid-cols-2 gap-6">
                                        <div class="bg-white rounded-xl p-6 shadow-md">
                                            <h3 class="text-lg font-medium text-gray-800 mb-4">학습 통계</h3>
                                            <div class="space-y-3">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">총 학습 시간</span>
                                                    <span class="font-medium text-gray-800">24시간 30분</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">일평균 학습 시간</span>
                                                    <span class="font-medium text-gray-800">2시간 15분</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">완료한 문제 수</span>
                                                    <span class="font-medium text-gray-800">156개</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-xl p-6 shadow-md">
                                            <h3 class="text-lg font-medium text-gray-800 mb-4">성취도</h3>
                                            <div class="space-y-3">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">평균 정답률</span>
                                                    <span class="font-medium text-gray-800">78%</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">최고 점수</span>
                                                    <span class="font-medium text-gray-800">95점</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">연속 학습일</span>
                                                    <span class="font-medium text-gray-800">7일</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </section>
    </div>

    <!-- FAQ 팝업 -->
    <div id="faq-popup" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b p-4 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800">자주하는 질문들</h3>
                <button onclick="closeFAQ()" class="p-2 hover:bg-gray-100 rounded-lg">
                    <span class="text-2xl">✕</span>
                </button>
            </div>
            <div id="faq-content" class="p-6 space-y-6">
                <!-- FAQ 내용이 여기에 동적으로 추가됩니다 -->
            </div>
        </div>
    </div>

    <!-- 비법노트 팝업 제거됨 - AI 비법노트 사용 -->


    <script>
        // PHP에서 전달받은 사용자 정보
        const phpUserData = <?php echo json_encode($user_json); ?>;
        
        // PHP에서 전달받은 대시보드 목표 데이터
        const dashboardGoalsData = <?php echo json_encode($dashboardGoals); ?>;
        
        // 전역 상태 변수들
        // URL 파라미터로 대시보드 직접 접근 가능
        const urlParams = new URLSearchParams(window.location.search);
        let currentSection = urlParams.get('section') === 'dashboard' ? 4 : 0;
        
        // 대시보드 직접 접근 시 데이터가 있는지 확인
        const isDashboardDirect = urlParams.get('section') === 'dashboard';
        let completedSections = [];
        
        // 대시보드 직접 접근 시 섹션 완료 처리
        if (isDashboardDirect && phpUserData.school && phpUserData.grade && phpUserData.examType) {
            completedSections = [0, 1, 2, 3];
        }
        let userData = {
            userid: phpUserData.userid,
            school: phpUserData.school || '',
            grade: phpUserData.grade || '',
            examType: phpUserData.examType || ''
        };
        let examPeriod = {
            start: phpUserData.examStartDate || '',
            end: phpUserData.examEndDate || '',
            mathDate: phpUserData.mathExamDate || '',
            status: phpUserData.examStatus || 'expected'
        };
        let examScope = {
            content: phpUserData.examScope || '',
            status: phpUserData.examStatus || 'expected'
        };
        let studyPhase = phpUserData.studyStatus || 'type-study';
        
        // 디버깅: PHP에서 전달받은 데이터 확인
        console.log('=== PHP에서 전달받은 데이터 ===');
        console.log('phpUserData:', phpUserData);
        console.log('examPeriod:', examPeriod);
        console.log('examScope:', examScope);
        console.log('=== 개별 시험 날짜 값 확인 ===');
        console.log('examStartDate:', phpUserData.examStartDate);
        console.log('examEndDate:', phpUserData.examEndDate);  
        console.log('mathExamDate:', phpUserData.mathExamDate);
        console.log('examScope:', phpUserData.examScope);
        console.log('examStatus:', phpUserData.examStatus);
        
        // 학습 상태 매핑 (한글 -> 영어)
        const studyStatusReverseMap = {
            '개념공부': 'concept',
            '개념복습': 'concept-review',
            '유형공부': 'type-study'
        };
        
        // 저장된 학습 상태가 한글이면 영어로 변환
        if (studyStatusReverseMap[studyPhase]) {
            studyPhase = studyStatusReverseMap[studyPhase];
        }

        // 섹션별 가이드 텍스트
        const sectionGuides = [
            "안녕! 👋 시험 준비 시작하자! 먼저 기본 정보를 알려줘.",
            "시험 정보를 자세히 알려줘! 친구들 정보를 클릭해서 쉽게 입력할 수 있어! 📅",
            "시험 직전 3-5일, 이게 진짜 게임 체인저야! 🎯",
            "시작점을 선택해 주세요! 너에게 맞는 출발선을 찾아보자! 🚀",
            "모든 준비 완료! 이제 본격적으로 시작해보자! 🎉"
        ];

        // 섹션별 FAQ 데이터
        const sectionFAQs = [
            [ // 기본 정보 입력
                { question: "학교명을 정확히 모르겠어요", answer: "학교 이름의 일부만 입력해도 괜찮아요. 예: '대전' 입력 후 '대전고등학교' 선택" },
                { question: "왜 학교 정보가 필요한가요?", answer: "같은 학교 학생들의 시험 정보를 참고할 수 있고, 학교별 맞춤 가이드를 제공하기 위해서예요." },
                { question: "시험 종류를 잘못 선택했어요", answer: "걱정 마세요! 설정에서 언제든지 변경할 수 있습니다." }
            ],
            [ // 시험 정보
                { question: "시험 기간이 아직 안 나왔어요", answer: "작년 일정을 참고하여 '예상'으로 입력하고, 나중에 수정하세요." },
                { question: "범위가 너무 넓어요", answer: "AI가 핵심 단원을 분석해서 우선순위를 정해드릴게요." },
                { question: "수학을 안 봐요", answer: "다른 주요 과목의 시험일을 입력해주세요." }
            ],
            [ // 라스트 청킹
                { question: "라스트 청킹 효과가 정말 있나요?", answer: "실제 지도 경험을 바탕으로 한 검증된 방법입니다. 3-5일 전 워밍업으로 시험 컨디션이 크게 개선됩니다." },
                { question: "온라인으로도 가능한가요?", answer: "네, 온라인으로도 충분히 효과적입니다. 중요한 건 집중도입니다." },
                { question: "어떤 내용으로 워밍업 해야 하나요?", answer: "그동안 공부한 내용을 가볍게 훑어보고, 기출문제 위주로 풀어보세요." }
            ],
            [ // 학습 단계
                { question: "어느 단계부터 시작해야 하나요?", answer: "현재 실력에 맞춰 선택하세요. 확실하지 않으면 '개념공부'부터 시작하세요." },
                { question: "단계를 건너뛸 수 있나요?", answer: "실력이 충분하다면 가능하지만, 체계적 학습을 위해 순서대로 진행을 권장합니다." },
                { question: "각 단계는 며칠씩 해야 하나요?", answer: "개인차가 있지만 보통 2-3일 정도입니다. 이해도에 따라 조절하세요." }
            ],
            [ // 대시보드
                { question: "통계가 정확한가요?", answer: "학습 활동을 기반으로 실시간 집계됩니다. 꾸준히 기록해주세요." },
                { question: "목표 점수는 어떻게 정하나요?", answer: "이전 성적과 현재 실력을 고려해서 현실적이면서도 도전적인 목표를 세우세요." },
                { question: "친구와 비교할 수 있나요?", answer: "곧 추가될 예정이에요. 건전한 경쟁이 동기부여가 될 수 있어요!" }
            ]
        ];

        // 시험 종류 정보
        const examTypes = [
            { id: '1mid', name: '1학기 중간고사', emoji: '🌸' },
            { id: '1final', name: '1학기 기말고사', emoji: '☀️' },
            { id: '2mid', name: '2학기 중간고사', emoji: '🍂' },
            { id: '2final', name: '2학기 기말고사', emoji: '❄️' }
        ];

        // 친구 데이터 (동적으로 로드됨)
        let schoolFriends = [];
        let examFriendsData = null;

        // 친구 정보 로드 함수
        async function loadExamFriends() {
            if (!userData.school || !userData.grade || !userData.examType) {
                console.log('기본 정보가 부족하여 친구 정보를 로드할 수 없습니다');
                return;
            }

            // 로딩 상태 표시
            const friendsInfoDisplay = document.getElementById('friends-info-display');
            if (friendsInfoDisplay) {
                friendsInfoDisplay.innerHTML = `
                    <div class="text-center text-gray-500 py-4">
                        <div class="text-3xl mb-2">🔍</div>
                        <p>같은 시험 정보를 찾는 중...</p>
                    </div>
                `;
            }

            try {
                const url = `get_exam_friends.php?userid=${userData.userid}&school=${encodeURIComponent(userData.school)}&grade=${userData.grade}&examType=${userData.examType}`;
                console.log('친구 정보 요청 URL:', url);
                
                const response = await fetch(url);
                const result = await response.json();
                
                console.log('친구 정보 응답:', result);
                
                if (result.success) {
                    examFriendsData = result;
                    schoolFriends = result.friends || [];
                    
                    // 디버깅 정보 출력
                    if (result.debug) {
                        console.log('디버깅 정보:', result.debug);
                    }
                    
                    // 아바타 추가 (범위 정보는 DB에서 가져옴)
                    const avatars = ['😊', '😎', '🤓', '😄', '🙂', '😆', '🤗', '😋', '😌', '🥰'];
                    
                    schoolFriends.forEach((friend, index) => {
                        friend.avatar = avatars[index % avatars.length];
                        // friend.scope는 이미 DB에서 가져온 값 사용
                    });
                    
                    console.log('친구 정보 로드 완료:', schoolFriends.length + '명');
                    console.log('친구 목록:', schoolFriends);
                    updateFriendsDisplay();
                } else {
                    console.log('친구 정보 로드 실패:', result.message);
                    schoolFriends = []; // 빈 배열로 초기화
                    updateFriendsDisplay(); // 빈 상태도 표시
                }
            } catch (error) {
                console.error('친구 정보 로드 중 오류:', error);
                schoolFriends = []; // 빈 배열로 초기화
                updateFriendsDisplay(); // 오류 상태도 표시
            }
        }

        // 친구 정보 표시 업데이트 (하단 참고용)
        function updateFriendsDisplay() {
            const friendsInfoDisplay = document.getElementById('friends-info-display');
            
            if (!friendsInfoDisplay) return;
            
            if (!schoolFriends || schoolFriends.length === 0) {
                friendsInfoDisplay.innerHTML = `
                    <div class="text-center text-gray-500 py-4">
                        <div class="text-3xl mb-2">📭</div>
                        <p>아직 같은 시험 정보를 입력한 친구가 없습니다.</p>
                    </div>
                `;
                return;
            }
            
            let friendsHTML = '<div class="grid md:grid-cols-2 gap-4">';
            
            schoolFriends.forEach((friend, index) => {
                const statusClass = friend.status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                const statusText = friend.status === 'confirmed' ? '확정' : '예상';
                
                friendsHTML += `
                    <div class="bg-white rounded-lg p-4 border border-blue-200">
                        <div class="flex justify-between items-center mb-3">
                            <span class="font-medium flex items-center gap-2">
                                <span class="text-xl">${friend.avatar}</span>
                                익명 친구 ${index + 1}
                            </span>
                            <span class="text-xs px-2 py-1 rounded ${statusClass}">${statusText}</span>
                        </div>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600">📅 기간:</span>
                                <span class="font-medium">${friend.startDate} ~ ${friend.endDate}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600">🎯 수학:</span>
                                <span class="font-medium">${friend.examDate}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-gray-600">📚 범위:</span>
                                <span class="font-medium flex-1">${friend.scope}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            friendsHTML += '</div>';
            friendsHTML += '<p class="text-xs text-gray-500 mt-3 text-center">💡 친구들의 정보를 참고하여 시험 준비를 하세요!</p>';
            
            friendsInfoDisplay.innerHTML = friendsHTML;
            console.log('친구 정보 표시 업데이트 완료:', schoolFriends.length + '명');
        }
        
        // 기본 시험 날짜 설정 (대표성 있는 날짜)
        function setDefaultExamDates() {
            const today = new Date();
            const currentMonth = today.getMonth();
            const currentYear = today.getFullYear();
            
            // 학기별 기본 시험 날짜 설정
            let defaultStartDate, defaultEndDate, defaultMathDate;
            
            if (userData.examType === '1mid') {
                // 1학기 중간고사: 보통 4월 말
                defaultStartDate = new Date(currentYear, 3, 25); // 4월 25일
                defaultEndDate = new Date(currentYear, 3, 30); // 4월 30일
                defaultMathDate = new Date(currentYear, 3, 27); // 4월 27일
            } else if (userData.examType === '1final') {
                // 1학기 기말고사: 보통 7월 초
                defaultStartDate = new Date(currentYear, 6, 5); // 7월 5일
                defaultEndDate = new Date(currentYear, 6, 10); // 7월 10일
                defaultMathDate = new Date(currentYear, 6, 7); // 7월 7일
            } else if (userData.examType === '2mid') {
                // 2학기 중간고사: 보통 10월 중순
                defaultStartDate = new Date(currentYear, 9, 15); // 10월 15일
                defaultEndDate = new Date(currentYear, 9, 20); // 10월 20일
                defaultMathDate = new Date(currentYear, 9, 17); // 10월 17일
            } else if (userData.examType === '2final') {
                // 2학기 기말고사: 보통 12월 중순
                defaultStartDate = new Date(currentYear, 11, 15); // 12월 15일
                defaultEndDate = new Date(currentYear, 11, 20); // 12월 20일
                defaultMathDate = new Date(currentYear, 11, 17); // 12월 17일
            } else {
                // 기본값: 현재로부터 2주 후
                defaultStartDate = new Date(today.getTime() + (14 * 24 * 60 * 60 * 1000));
                defaultEndDate = new Date(today.getTime() + (19 * 24 * 60 * 60 * 1000));
                defaultMathDate = new Date(today.getTime() + (16 * 24 * 60 * 60 * 1000));
            }
            
            // 날짜 포맷팅 (YYYY-MM-DD)
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            
            // 입력 필드에 기본값 설정
            document.getElementById('exam-start').value = formatDate(defaultStartDate);
            document.getElementById('exam-end').value = formatDate(defaultEndDate);
            document.getElementById('math-date').value = formatDate(defaultMathDate);
            
            // examPeriod 객체 업데이트
            examPeriod.start = formatDate(defaultStartDate);
            examPeriod.end = formatDate(defaultEndDate);
            examPeriod.mathDate = formatDate(defaultMathDate);
            
            // D-Day 업데이트
            updateDDay();
        }

        // 초기화
        document.addEventListener('DOMContentLoaded', function() {
            updateCurrentTime();
            setInterval(updateCurrentTime, 1000);
            startTypingEffect();
            
            // 대시보드 데이터 항상 표시 (섹션에 관계없이)
            displayDashboardGoals();
            
            // 학생 정보 로드
            loadStudentInfo();
            
            // 저장된 데이터로 폼 필드 채우기
            populateSavedData();
            
            // LMS 데이터 자동으로 불러오기
            if (!userData.school || !userData.grade) {
                loadLMSData();
            }
            
            // 학교 입력 필드에 이벤트 리스너 추가
            const schoolInput = document.getElementById('school-input');
            if (schoolInput) {
                schoolInput.addEventListener('input', function() {
                    const schoolName = this.value.toLowerCase();
                    const elementaryGrades = document.getElementById('elementary-grades');
                    
                    if (schoolName.includes('초등') || schoolName.includes('초교')) {
                        // 초등학교인 경우 4-6학년 버튼 표시
                        if (elementaryGrades) {
                            elementaryGrades.classList.remove('hidden');
                        }
                    } else {
                        // 초등학교가 아닌 경우 4-6학년 버튼 숨김
                        if (elementaryGrades) {
                            elementaryGrades.classList.add('hidden');
                        }
                    }
                });
            }
            
            // 대시보드 정보를 항상 업데이트 (섹션에 관계없이)
            console.log('=== DOMContentLoaded: 대시보드 정보 업데이트 시작 ===');
            
            // 대시보드 요소 확인
            console.log('dashboard-scope 요소 존재:', document.getElementById('dashboard-scope') !== null);
            console.log('exam-start-display 요소 존재:', document.getElementById('exam-start-display') !== null);
            console.log('exam-end-display 요소 존재:', document.getElementById('exam-end-display') !== null);
            console.log('dashboard-dday 요소 존재:', document.getElementById('dashboard-dday') !== null);
            
            // 즉시 업데이트
            updateDashboardInfo();
            
            // 500ms 후 재시도
            setTimeout(() => {
                console.log('DOMContentLoaded 500ms 후 대시보드 업데이트');
                updateDashboardInfo();
            }, 500);
            
            // 1초 후 재시도
            setTimeout(() => {
                console.log('DOMContentLoaded 1초 후 대시보드 업데이트');
                updateDashboardInfo();
            }, 1000);
            
            // 현재 섹션이 대시보드(4)인 경우 추가 처리
            if (currentSection === 4) {
                console.log('현재 섹션이 대시보드입니다. 데이터 표시 시작...');
                // 대시보드로 바로 이동
                goToSection(4);
                // DOM이 완전히 로드된 후 업데이트
                const checkAndUpdate = () => {
                    const section = document.getElementById('section-4');
                    if (section) {
                        console.log('대시보드 섹션 발견, 데이터 업데이트 시작');
                        updateDashboardInfo();
                    } else {
                        console.log('대시보드 섹션을 찾을 수 없음, 재시도...');
                        setTimeout(checkAndUpdate, 500);
                    }
                };
                setTimeout(checkAndUpdate, 500);
            }
            
            // 링크 입력 실시간 검사 (DOM이 준비된 후 설정)
            setTimeout(() => {
                const linkInput = document.getElementById('resource-link');
                if (linkInput) {
                    linkInput.addEventListener('input', function() {
                        if (currentUploadMode === 'link') {
                            const uploadBtn = document.getElementById('upload-resources-btn');
                            if (uploadBtn) {
                                uploadBtn.disabled = !this.value.trim();
                            }
                            
                            // 링크 미리보기 업데이트
                            updateLinkPreview(this.value.trim());
                        }
                    });
                }
            }, 100); // 약간의 지연으로 DOM 완전 로드 대기
        });

        // 링크 미리보기 업데이트
        function updateLinkPreview(url) {
            const preview = document.getElementById('link-preview');
            const content = document.getElementById('link-preview-content');
            
            if (!preview || !content) {
                return; // 요소가 없으면 종료
            }
            
            if (!url) {
                preview.classList.add('hidden');
                return;
            }
            
            try {
                const urlObj = new URL(url);
                let previewText = '';
                
                if (url.includes('drive.google.com')) {
                    previewText = '🔗 구글 드라이브 링크';
                } else if (url.includes('onedrive.live.com') || url.includes('1drv.ms')) {
                    previewText = '🔗 원드라이브 링크';
                } else if (url.includes('dropbox.com')) {
                    previewText = '🔗 드롭박스 링크';
                } else if (url.includes('youtube.com') || url.includes('youtu.be')) {
                    previewText = '🎥 유튜브 링크';
                } else {
                    previewText = '🔗 웹 링크';
                }
                
                previewText += `<br><span class="text-xs text-gray-500">${urlObj.hostname}</span>`;
                content.innerHTML = previewText;
                preview.classList.remove('hidden');
            } catch (e) {
                preview.classList.add('hidden');
            }
        }

        // 현재 시간 업데이트
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' });
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }

        // 타이핑 효과 시작
        function startTypingEffect() {
            typeText(`guide-text-${currentSection}`, sectionGuides[currentSection], function() {
                setTimeout(() => {
                    const content = document.getElementById(`section-${currentSection}-content`);
                    if (content) {
                        content.classList.remove('hidden');
                        content.classList.add('animate-fadeIn');
                    }
                }, 500);
            });
        }

        // 타이핑 효과 함수
        function typeText(elementId, text, callback) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            element.textContent = '';
            let index = 0;
            
            const timer = setInterval(() => {
                if (index < text.length) {
                    element.textContent += text[index];
                    index++;
                } else {
                    clearInterval(timer);
                    if (callback) callback();
                }
            }, 30);
        }

        // 토스트 알림 표시
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastContent = document.getElementById('toast-content');
            const toastIcon = document.getElementById('toast-icon');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            
            if (type === 'success') {
                toastContent.className = 'px-6 py-3 rounded-xl shadow-xl border-2 flex items-center gap-2 bg-green-50 border-green-200 text-green-800';
                toastIcon.textContent = '✅';
            } else if (type === 'info') {
                toastContent.className = 'px-6 py-3 rounded-xl shadow-xl border-2 flex items-center gap-2 bg-blue-50 border-blue-200 text-blue-800';
                toastIcon.textContent = 'ℹ️';
            } else {
                toastContent.className = 'px-6 py-3 rounded-xl shadow-xl border-2 flex items-center gap-2 bg-red-50 border-red-200 text-red-800';
                toastIcon.textContent = '⚠️';
            }
            
            toast.classList.remove('hidden');
            
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }

        // 섹션으로 이동
        function goToSection(sectionIndex) {
            if (sectionIndex <= currentSection || completedSections.includes(sectionIndex - 1)) {
                currentSection = sectionIndex;
                updateNavigation();
                const section = document.getElementById(`section-${sectionIndex}`);
                if (section) {
                    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                // 대시보드 섹션으로 이동 시 데이터 표시
                if (sectionIndex === 4) {
                    console.log('대시보드 섹션으로 이동 중...');
                    // 여러 번 시도하여 확실하게 업데이트
                    const updateAttempts = [300, 600, 1000, 1500];
                    updateAttempts.forEach(delay => {
                        setTimeout(() => {
                            console.log(`대시보드 업데이트 시도 (${delay}ms 후)`);
                            updateDashboardInfo();
                            displayDashboardGoals();
                            loadLMSDataForDashboard(); // LMS 데이터도 로드
                        }, delay);
                    });
                }
            }
        }

        // 네비게이션 업데이트
        function updateNavigation() {
            const navBtns = document.querySelectorAll('.nav-btn');
            navBtns.forEach((btn, index) => {
                const isCompleted = completedSections.includes(index);
                const isCurrent = currentSection === index;
                const isAccessible = index === 0 || completedSections.includes(index - 1) || isCompleted;
                
                btn.className = 'nav-btn flex flex-col items-center gap-1 px-2 py-1 rounded-lg transition-all text-xs';
                
                if (isCurrent) {
                    btn.classList.add('bg-indigo-100', 'text-indigo-700', 'scale-105');
                } else if (isCompleted) {
                    btn.classList.add('text-green-600', 'hover:bg-green-50');
                    // 완료 체크 표시 추가
                    const iconContainer = btn.querySelector('.flex.items-center.gap-1');
                    if (!iconContainer.querySelector('.check-mark')) {
                        const checkMark = document.createElement('span');
                        checkMark.className = 'check-mark text-green-500';
                        checkMark.textContent = '✓';
                        iconContainer.appendChild(checkMark);
                    }
                } else if (isAccessible) {
                    btn.classList.add('text-gray-600', 'hover:bg-gray-50');
                    btn.disabled = false;
                } else {
                    btn.classList.add('text-gray-400', 'cursor-not-allowed');
                    btn.disabled = true;
                }
            });
        }


        // 진행률 업데이트
        function updateProgress() {
            const percentage = (completedSections.length / 4) * 100;
            document.getElementById('progress-percentage').textContent = `${Math.round(percentage)}%`;
            document.getElementById('progress-bar').style.width = `${percentage}%`;
        }

        // 저장된 데이터로 폼 필드 채우기
        function populateSavedData() {
            console.log('=== populateSavedData 함수 시작 ===');
            console.log('userData:', userData);
            console.log('examPeriod:', examPeriod);
            console.log('examScope:', examScope);
            console.log('phpUserData 전체:', phpUserData);
            console.log('시험 날짜 값들:');
            console.log('- examPeriod.start:', examPeriod.start);
            console.log('- examPeriod.end:', examPeriod.end);
            console.log('- examPeriod.mathDate:', examPeriod.mathDate);
            console.log('- phpUserData.examStartDate:', phpUserData.examStartDate);
            console.log('- phpUserData.examEndDate:', phpUserData.examEndDate);
            console.log('- phpUserData.mathExamDate:', phpUserData.mathExamDate);
            
            // PHP에서 전달된 LMS 데이터 활용
            const lmsData = <?php echo json_encode($user_json['lmsData'] ?? array()); ?>;
            const birthYear = <?php echo json_encode($user_json['birthYear'] ?? 0); ?>;
            const semester = <?php echo json_encode($user_json['semester'] ?? 1); ?>;
            
            // 학교 입력 필드
            if (userData.school) {
                document.getElementById('school-input').value = userData.school;
                document.getElementById('header-school').textContent = userData.school;
                
                // 초등학교인 경우 4-6학년 버튼 표시
                const schoolName = userData.school.toLowerCase();
                const elementaryGrades = document.getElementById('elementary-grades');
                if ((schoolName.includes('초등') || schoolName.includes('초교')) && elementaryGrades) {
                    elementaryGrades.classList.remove('hidden');
                }
            }
            
            // 학년 선택 (PHP에서 계산된 값 사용)
            if (userData.grade) {
                // 학년 문자열에서 숫자만 추출 (예: '고3' -> '3')
                const gradeNumber = userData.grade.replace(/[^0-9]/g, '');
                if (gradeNumber) {
                    selectGrade(gradeNumber);
                }
            }
            
            // 시험 종류 자동 선택 (날짜 기반)
            const defaultExamType = <?php echo json_encode($user_json['defaultExamType'] ?? ''); ?>;
            const savedExamType = <?php echo json_encode($user_json['savedExamType'] ?? ''); ?>;
            console.log('현재 날짜:', new Date());
            console.log('PHP에서 계산된 기본 시험 종류:', defaultExamType);
            console.log('저장된 시험 종류:', savedExamType);
            
            // 항상 현재 날짜에 맞는 시험 종류를 자동 선택
            // 단, 사용자가 수동으로 변경한 경우는 유지
            if (true) {  // 항상 자동 선택
                // PHP에서 계산된 값 우선 사용
                if (defaultExamType) {
                    console.log('PHP 기본값 사용:', defaultExamType);
                    selectExamType(defaultExamType);
                } else {
                    // 클라이언트 측에서 계산
                    const currentDate = new Date();
                    const month = currentDate.getMonth() + 1;
                    const day = currentDate.getDate();
                    let examType = '';
                    
                    console.log('클라이언트 측 계산 - 월:', month, '일:', day);
                    
                    // 날짜별 시험 종류 판단 (2025년 기준)
                    if (month === 12 && day >= 11) {
                        examType = '1mid'; // 12월 11일부터 1학기 중간고사
                    } else if (month >= 1 && month <= 4) {
                        examType = '1mid'; // 1월~4월은 1학기 중간고사
                    } else if (month === 5 && day === 1) {
                        examType = '1mid'; // 5월 1일까지 1학기 중간고사
                    } else if (month === 5 && day >= 2) {
                        examType = '1final'; // 5월 2일부터 1학기 기말고사
                    } else if (month === 6) {
                        examType = '1final'; // 6월은 1학기 기말고사
                    } else if (month >= 7 && month <= 9) {
                        examType = '2mid'; // 7월~9월은 2학기 중간고사
                    } else if (month >= 10 && month <= 11) {
                        examType = '2final'; // 10월~11월은 2학기 기말고사
                    } else if (month === 12 && day <= 10) {
                        examType = '2final'; // 12월 10일까지 2학기 기말고사
                    }
                    
                    console.log('계산된 시험 종류:', examType);
                    
                    if (examType) {
                        selectExamType(examType);
                    }
                }
            } else {
                // 기존 저장된 값 사용
                console.log('기존 저장된 값 사용:', userData.examType);
                selectExamType(userData.examType);
            }
            
            // 시험 날짜 필드
            const representativeDate = <?php echo json_encode($user_json['representativeDate'] ?? ''); ?>;
            
            console.log('=== 시험 날짜 설정 시작 ===');
            console.log('examPeriod.start:', examPeriod.start);
            console.log('representativeDate:', representativeDate);
            
            const examStartInput = document.getElementById('exam-start');
            const examEndInput = document.getElementById('exam-end');
            const mathDateInput = document.getElementById('math-date');
            
            if (examStartInput) {
                if (examPeriod.start) {
                    console.log('시작일 설정:', examPeriod.start);
                    examStartInput.value = examPeriod.start;
                    const statusEl = document.getElementById('exam-start-status');
                    if (statusEl) {
                        statusEl.textContent = '확정';
                        statusEl.className = 'text-xs px-2 py-1 rounded bg-green-100 text-green-700';
                    }
                } else if (representativeDate) {
                    // 저장된 날짜가 없으면 대표성 있는 날짜 자동 입력
                    console.log('대표 날짜 설정:', representativeDate);
                    examStartInput.value = representativeDate;
                    const statusEl = document.getElementById('exam-start-status');
                    if (statusEl) {
                        statusEl.textContent = '예상';
                        statusEl.className = 'text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-700';
                    }
                }
            } else {
                console.log('exam-start input 요소를 찾을 수 없습니다.');
            }
            
            if (examEndInput && examPeriod.end) {
                console.log('종료일 설정:', examPeriod.end);
                examEndInput.value = examPeriod.end;
                const statusEl = document.getElementById('exam-end-status');
                if (statusEl) {
                    statusEl.textContent = '확정';
                    statusEl.className = 'text-xs px-2 py-1 rounded bg-green-100 text-green-700';
                }
            }
            
            if (mathDateInput && examPeriod.mathDate) {
                console.log('수학 시험일 설정:', examPeriod.mathDate);
                mathDateInput.value = examPeriod.mathDate;
            }
            
            // 시험 범위
            const examScopeInput = document.getElementById('exam-scope');
            if (examScopeInput && examScope.content) {
                console.log('시험 범위 설정:', examScope.content);
                examScopeInput.value = examScope.content;
            }
            
            // 모든 필드 설정 후 updateDDay 호출
            if (examPeriod.start || examPeriod.end || examPeriod.mathDate) {
                updateDDay(); // D-Day 업데이트
            }
            
            // 예상/확정 상태
            if (examScope.status) {
                const statusRadio = document.querySelector(`input[name="status"][value="${examScope.status}"]`);
                if (statusRadio) {
                    statusRadio.checked = true;
                    updateExamStatus(examScope.status);
                }
            } else {
                // 기본값은 예상
                updateExamStatus('expected');
            }
            
            // 섹션 0 완료 체크
            checkSection0Complete();
            
            // 섹션 1 완료 체크
            checkSection1Complete();
            
            // 정보 입력 섹션 즉시 표시
            const section0Content = document.getElementById('section-0-content');
            if (section0Content) {
                section0Content.classList.remove('hidden');
                section0Content.classList.add('animate-fadeIn');
            }
            
            // 시험 설정 섹션도 표시
            const section1Content = document.getElementById('section-1-content');
            if (section1Content) {
                section1Content.classList.remove('hidden');
                section1Content.classList.add('animate-fadeIn');
            }
            
            // 친구 정보 로드
            if (userData.school && userData.grade && userData.examType) {
                // loadSchoolFriends가 정의되어 있으면 호출
                if (typeof loadSchoolFriends === 'function') {
                    loadSchoolFriends();
                }
                // 시험 친구 정보도 로드
                if (typeof loadExamFriends === 'function') {
                    loadExamFriends();
                }
            }
            
            // 저장된 정보가 있으면 진행된 섹션 표시
            if (userData.school && userData.grade && userData.examType) {
                // 섹션 0 완료
                if (!completedSections.includes(0)) {
                    completedSections.push(0);
                }
                
                // 시험 정보도 있으면 섹션 1도 완료
                if (examPeriod.start && examPeriod.end && examPeriod.mathDate) {
                    if (!completedSections.includes(1)) {
                        completedSections.push(1);
                    }
                }
                
                // 진행률 업데이트
                updateProgress();
                updateNavigation();
                
                // 대시보드 정보 업데이트 - 여러 번 시도하여 확실하게 업데이트
                console.log('populateSavedData에서 대시보드 업데이트 시작');
                updateDashboardInfo();
                loadLMSDataForDashboard(); // LMS 데이터도 로드
                
                // DOM이 완전히 로드된 후 다시 업데이트
                setTimeout(() => {
                    console.log('populateSavedData 500ms 후 대시보드 재업데이트');
                    updateDashboardInfo();
                    loadLMSDataForDashboard();
                }, 500);
                
                setTimeout(() => {
                    console.log('populateSavedData 1000ms 후 대시보드 재업데이트');
                    updateDashboardInfo();
                    loadLMSDataForDashboard();
                }, 1000);
            }
        }

        // 학년 선택
        function selectGrade(grade, evt) {
            // 숫자로 변환
            grade = String(grade);
            userData.grade = grade;
            
            // 모든 버튼 초기화
            document.querySelectorAll('.grade-btn').forEach(btn => {
                btn.className = 'grade-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 transition-all transform hover:scale-105';
            });
            
            // 선택된 버튼 스타일 적용
            // 이벤트가 전달되었거나 전역 event 객체가 있는 경우
            const currentEvent = evt || (typeof event !== 'undefined' ? event : null);
            if (currentEvent && currentEvent.target && typeof currentEvent.target.closest === 'function') {
                const gradeBtn = currentEvent.target.closest('.grade-btn');
                if (gradeBtn) {
                    gradeBtn.className = 'grade-btn p-4 rounded-xl border-2 border-indigo-500 bg-indigo-50 transition-all transform hover:scale-105';
                }
            } else {
                // 프로그래매틱 호출 시
                document.querySelectorAll('.grade-btn').forEach(btn => {
                    const btnOnclick = btn.getAttribute('onclick');
                    if (btnOnclick && btnOnclick.includes(`'${grade}'`)) {
                        btn.className = 'grade-btn p-4 rounded-xl border-2 border-indigo-500 bg-indigo-50 transition-all transform hover:scale-105';
                    }
                });
            }
            
            // 헤더 업데이트
            const headerGrade = document.getElementById('header-grade');
            if (headerGrade) {
                // PHP에서 전달된 형식 (예: '고3', '중3')을 처리
                const phpGrade = <?php echo json_encode($grade); ?>;
                if (phpGrade && (phpGrade.includes('고') || phpGrade.includes('중'))) {
                    headerGrade.textContent = phpGrade;
                } else {
                    headerGrade.textContent = grade + '학년';
                }
            }
            
            checkSection0Complete();
        }

        // 시험 종류 선택
        function selectExamType(examType, evt) {
            console.log('selectExamType 호출:', examType);
            userData.examType = examType;
            
            // 모든 버튼 초기화
            document.querySelectorAll('.exam-btn').forEach(btn => {
                btn.className = 'exam-btn p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 bg-white transition-all transform hover:scale-105';
            });
            
            // 선택된 버튼 스타일 적용
            // 이벤트가 전달되었거나 전역 event 객체가 있는 경우
            const currentEvent = evt || (typeof event !== 'undefined' ? event : null);
            if (currentEvent && currentEvent.target && typeof currentEvent.target.closest === 'function') {
                const examBtn = currentEvent.target.closest('.exam-btn');
                if (examBtn) {
                    examBtn.className = 'exam-btn p-4 rounded-xl border-2 border-indigo-500 bg-indigo-50 transition-all transform hover:scale-105';
                }
            } else {
                // 프로그래매틱 호출 시
                document.querySelectorAll('.exam-btn').forEach(btn => {
                    const btnOnclick = btn.getAttribute('onclick');
                    if (btnOnclick && btnOnclick.includes(`'${examType}'`)) {
                        btn.className = 'exam-btn p-4 rounded-xl border-2 border-indigo-500 bg-indigo-50 transition-all transform hover:scale-105';
                    }
                });
            }
            
            // 헤더 업데이트
            const examTypeObj = examTypes.find(e => e.id === examType);
            const headerExam = document.getElementById('header-exam');
            if (examTypeObj && headerExam) {
                headerExam.textContent = examTypeObj.name;
            }
            
            checkSection0Complete();
            
            // 시험 종류가 선택되면 기본 시험 범위 불러오기
            if (userData.school && userData.grade && examType) {
                loadDefaultExamScope(userData.school, userData.grade, examType);
            }
        }

        // 섹션 0 완료 체크
        function checkSection0Complete() {
            const school = document.getElementById('school-input').value;
            userData.school = school;
            
            // 헤더 학교 업데이트
            if (school) {
                document.getElementById('header-school').textContent = school;
            }
            
            const nextBtn = document.getElementById('next-btn-0');
            if (userData.school && userData.grade && userData.examType) {
                nextBtn.disabled = false;
                nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                nextBtn.disabled = true;
                nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }


        // 친구 정보 적용 - 기간
        function applyFriendPeriod(friendIndex) { 
            const friend = schoolFriends[friendIndex];
            
            document.getElementById('exam-start').value = friend.startDate;
            document.getElementById('exam-end').value = friend.endDate;
            document.getElementById('math-date').value = friend.examDate;
            
            // 라디오 버튼 상태 설정
            const statusRadios = document.querySelectorAll('input[name="exam-status"]');
            statusRadios.forEach(radio => {
                if ((radio.value === 'confirmed' && friend.status === 'confirmed') ||
                    (radio.value === 'expected' && friend.status === 'expected')) {
                    radio.checked = true;
                }
            });
            
            examPeriod.start = friend.startDate;
            examPeriod.end = friend.endDate;
            examPeriod.mathDate = friend.examDate;
            examPeriod.status = friend.status;
            
            updateDDay();
            showToast(`익명 친구의 시험 기간 정보가 적용되었어요! 📅`);
            checkSection1Complete();
        }

        // 친구 정보 적용 - 범위
        function applyFriendScope(friendIndex) {
            const friend = schoolFriends[friendIndex];
            
            document.getElementById('exam-scope').value = friend.scope;
            examScope.content = friend.scope;
            examScope.status = friend.status;
            
            showToast(`익명 친구의 시험 범위 정보가 적용되었어요! 📚`);
            checkSection1Complete();
        }

        // 기본 시험 범위 불러오기
        async function loadDefaultExamScope(school, grade, examType) {
            try {
                const response = await fetch(`get_default_exam_scope.php?action=get_default_scope&school=${encodeURIComponent(school)}&grade=${grade}&examType=${examType}`);
                const result = await response.json();
                
                if (result.success && result.scope) {
                    const examScopeInput = document.getElementById('exam-scope');
                    if (examScopeInput && !examScopeInput.value) {
                        // 기존에 입력된 값이 없을 때만 자동 입력
                        examScopeInput.value = result.scope;
                        examScope.content = result.scope;
                        examScope.status = result.status;
                        
                        // 확인요망 표시 추가
                        const scopeLabel = examScopeInput.parentElement.querySelector('h3');
                        if (scopeLabel && !scopeLabel.querySelector('.bg-orange-100')) {
                            scopeLabel.innerHTML += ' <span class="ml-4 inline-flex items-center gap-1 text-sm bg-orange-500 text-white px-4 py-2 rounded-lg border-2 border-orange-600 font-bold shadow-lg hover:bg-orange-600 transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>확인요망</span>';
                        }
                        
                        console.log('기본 시험 범위 불러오기 성공:', result.message);
                    }
                }
            } catch (error) {
                console.error('기본 시험 범위 불러오기 오류:', error);
            }
        }
        
        // 친구 정보 펼치기/접기
        function toggleFriendsInfo() {
            const container = document.getElementById('friends-info-container');
            const icon = document.getElementById('friends-toggle-icon');
            
            if (container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
                
                // 친구 정보를 아직 로드하지 않았으면 로드
                if (!schoolFriends || schoolFriends.length === 0) {
                    loadExamFriends();
                }
            } else {
                container.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
        
        // 학교 홈페이지 열기
        async function openSchoolWebsite() {
            if (!userData.school) {
                showToast('학교 정보가 없습니다.', 'error');
                return;
            }
            
            const schoolWebsiteBtn = document.getElementById('school-website-btn');
            if (schoolWebsiteBtn) {
                schoolWebsiteBtn.disabled = true;
                schoolWebsiteBtn.innerHTML = '<span>🔍</span> <span>학교 홈페이지 검색 중...</span>';
            }
            
            try {
                // 먼저 기본 패턴으로 시도
                const response1 = await fetch(`search_school_website.php?school=${encodeURIComponent(userData.school)}`);
                const result1 = await response1.json();
                
                if (result1.success && result1.url) {
                    window.open(result1.url, '_blank');
                    showToast('학교 홈페이지로 이동합니다.', 'success');
                } else {
                    // 기본 패턴 실패 시 웹 검색 결과 사용
                    const searchUrl = await getGoogleSearchFirstResult(userData.school + ' 홈페이지');
                    if (searchUrl) {
                        window.open(searchUrl, '_blank');
                        showToast('학교 홈페이지로 이동합니다.', 'success');
                    } else {
                        // 최종적으로 구글 검색 페이지로 이동
                        const searchQuery = encodeURIComponent(userData.school + ' 홈페이지');
                        window.open(`https://www.google.com/search?q=${searchQuery}`, '_blank');
                        showToast('구글 검색으로 이동합니다.', 'info');
                    }
                }
            } catch (error) {
                console.error('학교 홈페이지 URL 가져오기 오류:', error);
                // 오류 시 기본 검색
                const searchQuery = encodeURIComponent(userData.school + ' 홈페이지');
                window.open(`https://www.google.com/search?q=${searchQuery}`, '_blank');
                showToast('구글 검색으로 이동합니다.', 'info');
            } finally {
                if (schoolWebsiteBtn) {
                    schoolWebsiteBtn.disabled = false;
                    schoolWebsiteBtn.innerHTML = '<span>🏫</span> <span>학교 홈페이지 바로가기</span> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>';
                }
            }
        }
        
        // Google 검색 결과에서 첫 번째 URL 가져오기
        async function getGoogleSearchFirstResult(query) {
            try {
                // 서버 측 프록시를 통해 검색
                const response = await fetch(`google_search_proxy.php?q=${encodeURIComponent(query)}`);
                const result = await response.json();
                
                if (result.success && result.firstUrl) {
                    return result.firstUrl;
                }
            } catch (error) {
                console.error('Google 검색 오류:', error);
            }
            return null;
        }
        
        // D-Day 업데이트
        function updateDDay() {
            const startDate = document.getElementById('exam-start').value;
            const endDate = document.getElementById('exam-end').value;
            const mathDate = document.getElementById('math-date').value;
            
            // examPeriod 변수 업데이트
            if (startDate) examPeriod.start = startDate;
            if (endDate) examPeriod.end = endDate;
            if (mathDate) examPeriod.mathDate = mathDate;
            
            if (mathDate) {
                const today = new Date();
                const examDate = new Date(mathDate);
                const diffTime = examDate - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                document.getElementById('dday-number').textContent = `D-${diffDays}`;
                document.getElementById('dday-display').classList.remove('hidden');
                
                // 대시보드의 D-Day도 업데이트
                const dashboardDDay = document.getElementById('dashboard-dday');
                if (dashboardDDay) {
                    dashboardDDay.textContent = `D-${diffDays}`;
                }
            }
            
            // 섹션 1 완료 체크도 호출
            checkSection1Complete();
        }

        // 섹션 1 완료 체크
        function checkSection1Complete() {
            const start = document.getElementById('exam-start').value;
            const end = document.getElementById('exam-end').value;
            const mathDate = document.getElementById('math-date').value;
            const scope = document.getElementById('exam-scope').value;
            
            examPeriod.start = start;
            examPeriod.end = end;
            examPeriod.mathDate = mathDate;
            examScope.content = scope;
            
            const nextBtn = document.getElementById('next-btn-1');
            if (start && end && mathDate && scope) {
                nextBtn.disabled = false;
                nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                nextBtn.disabled = true;
                nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        // 친구 섹션 토글
        function toggleFriends() {
            const friendsSection = document.getElementById('friends-section');
            if (friendsSection.style.display === 'none') {
                friendsSection.style.display = 'block';
            } else {
                friendsSection.style.display = 'none';
            }
        }

        // LMS 데이터 불러오기
        async function loadLMSData() {
            try {
                showToast('LMS 정보를 불러오는 중...', 'info');
                
                const response = await fetch(`get_user_lms_data.php?action=get_lms_data&userid=${userData.userid}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const lmsData = result.data;
                    
                    // 학교 정보 자동 입력
                    if (lmsData.institute && !userData.school) {
                        document.getElementById('school-input').value = lmsData.institute;
                        userData.school = lmsData.institute;
                        document.getElementById('header-school').textContent = lmsData.institute;
                        
                        // 초등학교인 경우 4-6학년 버튼 표시
                        const schoolName = lmsData.institute.toLowerCase();
                        const elementaryGrades = document.getElementById('elementary-grades');
                        if ((schoolName.includes('초등') || schoolName.includes('초교')) && elementaryGrades) {
                            elementaryGrades.classList.remove('hidden');
                        }
                    }
                    
                    // 학년 계산 함수
                    function calculateGradeFromBirthYear(birthYear) {
                        // 2025년 기준 학년 매핑
                        const gradeMap = {
                            2007: { grade: '3', level: 'high' },
                            2008: { grade: '2', level: 'high' },
                            2009: { grade: '1', level: 'high' },
                            2010: { grade: '3', level: 'middle' },
                            2011: { grade: '2', level: 'middle' },
                            2012: { grade: '1', level: 'middle' },
                            2013: { grade: '6', level: 'elementary' },
                            2014: { grade: '5', level: 'elementary' },
                            2015: { grade: '4', level: 'elementary' },
                            2016: { grade: '3', level: 'elementary' }
                        };
                        
                        if (gradeMap[birthYear]) {
                            const info = gradeMap[birthYear];
                            userData.gradeLevel = info.level;
                            return info.grade;
                        }
                        return null;
                    }
                    
                    // 학년 자동 계산 및 선택
                    if (!userData.grade && lmsData.birthdate) {
                        const birthYear = parseInt(lmsData.birthdate);
                        const grade = calculateGradeFromBirthYear(birthYear);
                        if (grade) {
                            selectGrade(grade);
                            userData.grade = grade;
                        }
                    } else if (lmsData.grade) {
                        const grade = String(lmsData.grade);
                        if (grade >= 1 && grade <= 3) {
                            selectGrade(grade);
                        }
                    }
                    
                    // 입력 상태 체크
                    checkSection0Complete();
                    
                    // 성공 메시지와 함께 불러온 정보 표시
                    let message = 'LMS 정보를 성공적으로 불러왔습니다!';
                    if (lmsData.institute) {
                        message += `\n학교: ${lmsData.institute}`;
                    }
                    if (lmsData.grade || lmsData.birthdate) {
                        const gradeText = userData.grade ? `${userData.grade}학년` : '학년 정보 없음';
                        message += `\n학년: ${gradeText}`;
                    }
                    
                    showToast(message, 'success');
                    
                    // 추가 정보가 있으면 콘솔에 로그
                    console.log('LMS 데이터 전체:', lmsData);
                    
                } else {
                    showToast('LMS 정보를 찾을 수 없습니다.', 'error');
                }
            } catch (error) {
                console.error('LMS 데이터 로드 오류:', error);
                showToast('LMS 정보 불러오기 실패: ' + error.message, 'error');
            }
        }

        // 대시보드용 LMS 데이터 로드 및 표시
        async function loadLMSDataForDashboard() {
            try {
                const response = await fetch(`get_user_lms_data.php?action=get_lms_data&userid=${userData.userid}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const lmsData = result.data;
                    
                    // 학습 모드 매핑
                    const lmodeMap = {
                        '신규': '🌱 신규',
                        '자율': '🎯 자율',
                        '지도': '👨‍🏫 지도',
                        '도제': '🎓 도제'
                    };
                    
                    // 스크롤 뷰와 탭 뷰 모두 업데이트하는 헬퍼 함수
                    const updateElements = (baseId, value) => {
                        const scrollEl = document.getElementById(`${baseId}-scroll`);
                        const tabEl = document.getElementById(baseId);
                        if (scrollEl) scrollEl.textContent = value;
                        if (tabEl) tabEl.textContent = value;
                    };
                    
                    // LMS 데이터 업데이트 (스크롤뷰와 탭뷰 동시에)
                    updateElements('lms-academy', lmsData.academy || '-');
                    updateElements('lms-location', lmsData.location || '-');
                    updateElements('lms-lmode', lmodeMap[lmsData.lmode] || lmsData.lmode || '-');
                    updateElements('lms-mathlevel', lmsData.mathlevel || '-');
                    updateElements('lms-termhours', lmsData.termhours ? lmsData.termhours + '시간' : '-');
                    
                    // 학습 스타일 정보
                    updateElements('lms-evaluate', lmsData.evaluate || '-');
                    updateElements('lms-curriculum', lmsData.curriculum || '-');
                    updateElements('lms-goalstability', lmsData.goalstability || '-');
                    
                    // PRESET 정보
                    updateElements('lms-preset-concept', lmsData.preset_concept || '-');
                    updateElements('lms-preset-advanced', lmsData.preset_advanced || '-');
                    updateElements('lms-preset-school', lmsData.preset_school || '-');
                    updateElements('lms-preset-csat', lmsData.preset_csat || '-');
                    
                    
                } else {
                    // LMS 데이터가 없을 때 기본값 표시 (스크롤뷰와 탭뷰 모두)
                    const updateElements = (baseId, value) => {
                        const scrollEl = document.getElementById(`${baseId}-scroll`);
                        const tabEl = document.getElementById(baseId);
                        if (scrollEl) scrollEl.textContent = value;
                        if (tabEl) tabEl.textContent = value;
                    };
                    
                    updateElements('lms-academy', '-');
                    updateElements('lms-location', '-');
                    updateElements('lms-lmode', '-');
                    updateElements('lms-mathlevel', '-');
                    updateElements('lms-termhours', '-');
                    updateElements('lms-evaluate', '-');
                    updateElements('lms-curriculum', '-');
                    updateElements('lms-goalstability', '-');
                    updateElements('lms-preset-concept', '-');
                    updateElements('lms-preset-advanced', '-');
                    updateElements('lms-preset-school', '-');
                    updateElements('lms-preset-csat', '-');
                }
            } catch (error) {
                console.error('대시보드 LMS 데이터 로드 오류:', error);
            }
        }
        
        // 추가 LMS 정보 표시 (학습 스타일 카드)
        function displayAdditionalLMSInfo(lmsData) {
            // 학습 스타일 정보가 있으면 중앙 영역에 추가 카드 생성
            if (lmsData.evaluate || lmsData.curriculum || lmsData.goalstability) {
                const centralColumn = document.querySelector('.col-span-6');
                if (!centralColumn) return;
                
                // 이미 추가된 카드가 있는지 확인
                let styleCard = document.getElementById('lms-learning-style-card');
                if (!styleCard) {
                    // 새 카드 생성
                    styleCard = document.createElement('div');
                    styleCard.id = 'lms-learning-style-card';
                    styleCard.className = 'mt-6 bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20';
                    
                    // 학습 현황 섹션 뒤에 추가
                    const learningSection = centralColumn.querySelector('.mt-8.space-y-6');
                    if (learningSection) {
                        learningSection.appendChild(styleCard);
                    }
                }
                
                // 카드 내용 업데이트
                let styleHTML = `
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                        <span>🧠</span>
                        LMS 학습 스타일 분석
                    </h4>
                    <div class="grid grid-cols-3 gap-4">
                `;
                
                // 평가 스타일
                if (lmsData.evaluate) {
                    const evaluateIcon = lmsData.evaluate === '완결형' ? '✅' : '🚀';
                    styleHTML += `
                        <div class="bg-gradient-to-br from-blue-500/20 to-indigo-500/20 rounded-lg p-3 text-center border border-blue-500/30">
                            <div class="text-2xl mb-1">${evaluateIcon}</div>
                            <p class="text-xs text-blue-300">평가 스타일</p>
                            <p class="text-sm font-bold text-blue-400">${lmsData.evaluate}</p>
                        </div>
                    `;
                }
                
                // 커리큘럼 유형
                if (lmsData.curriculum) {
                    const curriculumIcon = lmsData.curriculum === '성장형' ? '📈' : 
                                          lmsData.curriculum === '표준형' ? '📊' : '📉';
                    styleHTML += `
                        <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-lg p-3 text-center border border-green-500/30">
                            <div class="text-2xl mb-1">${curriculumIcon}</div>
                            <p class="text-xs text-green-300">커리큘럼</p>
                            <p class="text-sm font-bold text-green-400">${lmsData.curriculum}</p>
                        </div>
                    `;
                }
                
                // 목표 안정도
                if (lmsData.goalstability) {
                    const stabilityIcon = lmsData.goalstability === '안정' ? '🎯' : '🎲';
                    styleHTML += `
                        <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-lg p-3 text-center border border-purple-500/30">
                            <div class="text-2xl mb-1">${stabilityIcon}</div>
                            <p class="text-xs text-purple-300">목표 안정도</p>
                            <p class="text-sm font-bold text-purple-400">${lmsData.goalstability}</p>
                        </div>
                    `;
                }
                
                styleHTML += '</div>';
                styleCard.innerHTML = styleHTML;
            }
        }

        // 학습 단계 선택
        function selectPhase(phase) {
            studyPhase = phase;
            console.log('학습 단계 선택됨:', phase, '→', studyPhase);
            
            // 모든 단계 버튼 초기화
            document.querySelectorAll('.phase-btn').forEach(btn => {
                btn.className = 'phase-btn w-full p-6 rounded-2xl border-2 border-gray-200 hover:border-indigo-300 transition-all';
            });
            
            // 선택된 버튼 스타일 적용
            event.target.closest('.phase-btn').className = 'phase-btn w-full p-6 rounded-2xl border-2 border-indigo-500 bg-indigo-50 transition-all';
            
            setTimeout(() => completeSection(3), 300);
        }

        // 대시보드에서 단계 선택
        function selectDashboardPhase(phase) {
            studyPhase = phase;
            
            // 모든 단계 아이템 초기화
            document.querySelectorAll('.phase-item').forEach(item => {
                item.className = 'phase-item relative group cursor-pointer transition-all';
            });
            
            // 선택된 아이템 스타일 적용
            event.target.closest('.phase-item').className = 'phase-item relative group cursor-pointer transition-all scale-105';
            
            // 목표 텍스트 업데이트
            const phaseNames = {
                'concept': '개념공부',
                'concept-review': '개념복습',
                'type-study': '유형공부'
            };
            
            document.getElementById('daily-goal').textContent = `"${phaseNames[phase]} 완료하기!"`;
        }

        // 대시보드 모드 전환 함수
        function setDashboardMode(mode) {
            console.log('setDashboardMode 호출됨:', mode);
            
            const scrollBtn = document.getElementById('scroll-mode-btn');
            const tabBtn = document.getElementById('tab-mode-btn');
            const scrollContainer = document.getElementById('scroll-mode-container');
            const tabContainer = document.getElementById('tab-mode-container');
            
            // 요소 존재 확인
            if (!scrollBtn || !tabBtn || !scrollContainer || !tabContainer) {
                console.error('모드 전환에 필요한 요소들이 없습니다:', {
                    scrollBtn: !!scrollBtn,
                    tabBtn: !!tabBtn,
                    scrollContainer: !!scrollContainer,
                    tabContainer: !!tabContainer
                });
                return;
            }
            
            if (mode === 'scroll') {
                // 스크롤 모드 활성화
                console.log('스크롤 모드로 전환');
                scrollBtn.className = 'px-3 py-1 text-sm rounded bg-white text-gray-800 shadow-sm transition-all cursor-pointer';
                tabBtn.className = 'px-3 py-1 text-sm rounded text-gray-600 hover:text-gray-800 transition-all cursor-pointer';
                
                // 강제로 클래스 설정
                scrollContainer.style.display = 'block';
                tabContainer.style.display = 'none';
                scrollContainer.classList.remove('hidden');
                tabContainer.classList.add('hidden');
                
                console.log('스크롤 컨테이너 표시됨');
            } else if (mode === 'tab') {
                // 탭 모드 활성화
                console.log('탭 모드로 전환');
                tabBtn.className = 'px-3 py-1 text-sm rounded bg-white text-gray-800 shadow-sm transition-all cursor-pointer';
                scrollBtn.className = 'px-3 py-1 text-sm rounded text-gray-600 hover:text-gray-800 transition-all cursor-pointer';
                
                // 강제로 클래스 설정
                tabContainer.style.display = 'block';
                scrollContainer.style.display = 'none';
                tabContainer.classList.remove('hidden');
                scrollContainer.classList.add('hidden');
                
                // 탭 모드 데이터 업데이트
                updateTabModeData();
                
                console.log('탭 컨테이너 표시됨');
            }
        }
        
        // 탭 선택 함수 - 개선된 버전
        function selectTab(tabName) {
            // 모든 탭 버튼 초기화
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = 'tab-btn flex-1 whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-all';
            });
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            
            // 선택된 탭 활성화
            event.target.className = 'tab-btn flex-1 whitespace-nowrap px-4 py-3 text-sm font-medium bg-blue-500 text-white rounded-lg transition-all';
            document.getElementById(`tab-${tabName}`).classList.remove('hidden');
        }
        
        // 탭 모드 데이터 업데이트
        function updateTabModeData() {
            // 학교 정보
            const tabSchool = document.getElementById('tab-school');
            if (tabSchool) tabSchool.textContent = userData.school || '-';
            
            // 학년
            const tabGrade = document.getElementById('tab-grade');
            if (tabGrade) tabGrade.textContent = userData.grade ? `${userData.grade}학년` : '-';
            
            // 시험 종류
            const tabExam = document.getElementById('tab-exam');
            if (tabExam && userData.examType) {
                const examType = examTypes.find(e => e.id === userData.examType);
                tabExam.textContent = examType ? examType.name : '-';
            }
            
            // 시험 기간
            const tabPeriod = document.getElementById('tab-period');
            if (tabPeriod && examPeriod.start && examPeriod.end) {
                tabPeriod.textContent = `${formatDate(examPeriod.start)} ~ ${formatDate(examPeriod.end)}`;
            }
            
            // D-Day
            const tabDday = document.getElementById('tab-dday');
            if (tabDday) {
                tabDday.textContent = document.getElementById('dashboard-dday').textContent;
            }
            
            // 시험 범위
            const tabScope = document.getElementById('tab-scope');
            if (tabScope) {
                tabScope.textContent = examScope.content || '범위 미입력';
            }
        }
        
        // 현재 시간 업데이트 함수
        function updateCurrentTime() {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = `${hours}:${minutes}`;
            }
        }
        
        // 1초마다 시간 업데이트
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime();
        
        // 알림 표시 함수
        function showNotifications() {
            showToast('알림 기능은 준비 중입니다.', 'info');
        }
        

        // FAQ 표시
        function showFAQ() {
            const faqPopup = document.getElementById('faq-popup');
            const faqContent = document.getElementById('faq-content');
            
            // 현재 섹션의 FAQ 로드
            const faqs = sectionFAQs[currentSection] || [];
            faqContent.innerHTML = '';
            
            faqs.forEach((faq, idx) => {
                const faqItem = document.createElement('div');
                faqItem.className = 'border-b pb-4 last:border-0';
                faqItem.innerHTML = `
                    <h4 class="font-semibold text-gray-800 mb-2 flex items-start gap-2">
                        <span class="text-indigo-600 mt-1">Q${idx + 1}.</span>
                        ${faq.question}
                    </h4>
                    <p class="text-gray-600 ml-7">${faq.answer}</p>
                `;
                faqContent.appendChild(faqItem);
            });
            
            faqPopup.classList.remove('hidden');
        }

        // FAQ 닫기
        function closeFAQ() {
            document.getElementById('faq-popup').classList.add('hidden');
        }

        // 비법노트 열기 - AI 비법노트로 통합
        function openTipsChat() {
            openAIChat();
        }

        // 채팅 메시지 전송 (GPT API 사용)
        async function sendChatMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            // 사용자 메시지 추가
            addChatMessage(message, 'user');
            input.value = '';
            
            // GPT API 호출
            try {
                // 타이핑 표시
                const typingDiv = document.createElement('div');
                typingDiv.id = 'typing-indicator-legacy';
                typingDiv.className = 'mb-4 text-left';
                typingDiv.innerHTML = `
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-2xl">🧮</span>
                        <span class="text-sm font-medium text-gray-600">AI 선배</span>
                        <span class="text-xs text-gray-400">생각 중...</span>
                    </div>
                `;
                document.getElementById('chat-messages').appendChild(typingDiv);
                
                // GPT API 호출
                const response = await fetch('gpt_chat_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'chat',
                        school: userData.school || '',
                        grade: userData.grade || 1,
                        examType: userData.examType || '',
                        message: message,
                        conversation: []
                    })
                });
                
                const data = await response.json();
                
                // 타이핑 표시 제거
                const indicator = document.getElementById('typing-indicator-legacy');
                if (indicator) indicator.remove();
                
                if (data.success) {
                    addChatMessage(data.response, 'senior');
                } else {
                    throw new Error(data.error || '응답 실패');
                }
            } catch (error) {
                console.error('GPT 응답 실패:', error);
                const indicator = document.getElementById('typing-indicator-legacy');
                if (indicator) indicator.remove();
                addChatMessage('죄송해요! 잠시 연결이 불안정해요. 다시 물어봐주세요! 😅', 'senior');
            }
        }

        // 채팅 메시지 추가
        function addChatMessage(message, sender) {
            const chatMessages = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            const currentTime = new Date().toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' });
            
            if (sender === 'user') {
                messageDiv.className = 'mb-4 text-right';
                messageDiv.innerHTML = `
                    <div class="inline-block max-w-xs lg:max-w-md bg-indigo-600 text-white rounded-2xl rounded-tr-none p-4 shadow-sm">
                        <p class="text-white">${message}</p>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">${currentTime}</p>
                `;
            } else {
                messageDiv.className = 'mb-4 text-left';
                messageDiv.innerHTML = `
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-2xl">🤖</span>
                        <span class="text-sm font-medium text-gray-600">AI 튜터</span>
                        <span class="text-xs text-gray-400">${currentTime}</span>
                    </div>
                    <div class="inline-block max-w-xs lg:max-w-md bg-white border border-gray-200 rounded-2xl rounded-tl-none p-4 shadow-sm">
                        <p class="text-gray-800">${message}</p>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            // 부드러운 스크롤 애니메이션
            chatMessages.scrollTo({
                top: chatMessages.scrollHeight,
                behavior: 'smooth'
            });
        }

        // 기존 비법노트 관련 함수들 제거됨 - AI 비법노트 사용

        // 채팅 키보드 이벤트 (AI 비법노트용)
        function handleChatKeyPress(event) {
            if (event.key === 'Enter') {
                sendChatMessage();
            }
        }

        // 업데이트 알림 닫기
        function dismissAlert() {
            document.getElementById('update-alert').style.display = 'none';
        }

        // 알림 보기 (더미 함수)
        function showNotifications() {
            showToast('알림 기능이 곧 추가될 예정이에요! 🔔');
        }

        // 시험 정보 보기 (더미 함수)
        function showExamInfo() {
            showToast('시험 정보 보기 기능이 곧 추가될 예정이에요! 📄');
        }

        // ==== 시험 정보 업로드 관련 함수들 ====
        
        let selectedFiles = [];
        let currentUploadType = '';

        // 알림 관련 전역 변수
        let currentUserId = 1; // 실제로는 로그인한 사용자 ID를 사용해야 함
        let notificationCheckInterval = null;
        
        // AI 채팅 관련 전역 변수
        let aiChatConversation = [];
        let isAIProcessing = false;
        
        // 알림 팝업 관련 함수들
        function showNotifications() {
            document.getElementById('notifications-popup').classList.remove('hidden');
            loadNotifications();
        }
        
        function closeNotifications() {
            document.getElementById('notifications-popup').classList.add('hidden');
        }
        
        async function loadNotifications() {
            // 로딩 표시
            document.getElementById('notifications-loading').classList.remove('hidden');
            document.getElementById('notifications-content').classList.add('hidden');
            document.getElementById('no-notifications').classList.add('hidden');
            
            try {
                const response = await fetch(`get_notifications.php?action=fetch&user_id=${currentUserId}`);
                const data = await response.json();
                
                // 로딩 숨기기
                document.getElementById('notifications-loading').classList.add('hidden');
                
                if (data.success) {
                    // 읽지 않은 알림 수 업데이트
                    updateNotificationBadge(data.unread_count);
                    document.getElementById('notification-count').textContent = `읽지 않은 알림: ${data.unread_count}개`;
                    
                    if (data.notifications && data.notifications.length > 0) {
                        const notificationsContent = document.getElementById('notifications-content');
                        notificationsContent.innerHTML = '';
                        
                        data.notifications.forEach(notification => {
                            const notificationItem = document.createElement('div');
                            notificationItem.className = `p-4 rounded-lg border ${notification.is_read ? 'bg-gray-50 border-gray-200' : 'bg-blue-50 border-blue-200'}`;
                            
                            const icon = notification.resource_type === 'file' ? '📁' : 
                                        notification.resource_type === 'tip' ? '💡' : '🔗';
                            
                            notificationItem.innerHTML = `
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="text-2xl">${icon}</span>
                                        <div>
                                            <p class="font-medium ${notification.is_read ? 'text-gray-800' : 'text-blue-800'}">${notification.message}</p>
                                            <p class="text-sm text-gray-600 mt-1">${notification.exam_info}</p>
                                            <p class="text-xs text-gray-500 mt-2">${notification.time_ago}</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        ${!notification.is_read ? `
                                            <button onclick="markAsRead(${notification.notification_id})" class="text-blue-600 hover:text-blue-700">
                                                <span title="읽음 표시">✓</span>
                                            </button>
                                        ` : ''}
                                        <button onclick="deleteNotification(${notification.notification_id})" class="text-red-600 hover:text-red-700">
                                            <span title="삭제">✕</span>
                                        </button>
                                    </div>
                                </div>
                            `;
                            
                            notificationsContent.appendChild(notificationItem);
                        });
                        
                        notificationsContent.classList.remove('hidden');
                    } else {
                        document.getElementById('no-notifications').classList.remove('hidden');
                    }
                }
            } catch (error) {
                console.error('알림 로드 실패:', error);
                document.getElementById('notifications-loading').classList.add('hidden');
                document.getElementById('no-notifications').classList.remove('hidden');
            }
        }
        
        async function markAsRead(notificationId) {
            try {
                const response = await fetch('get_notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_read&user_id=${currentUserId}&notification_id=${notificationId}`
                });
                
                const data = await response.json();
                if (data.success) {
                    loadNotifications(); // 목록 새로고침
                }
            } catch (error) {
                console.error('읽음 처리 실패:', error);
            }
        }
        
        async function markAllRead() {
            try {
                const response = await fetch('get_notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_read&user_id=${currentUserId}`
                });
                
                const data = await response.json();
                if (data.success) {
                    loadNotifications(); // 목록 새로고침
                    showToast('모든 알림을 읽음으로 표시했습니다');
                }
            } catch (error) {
                console.error('모두 읽음 처리 실패:', error);
            }
        }
        
        async function deleteNotification(notificationId) {
            if (!confirm('이 알림을 삭제하시겠습니까?')) return;
            
            try {
                const response = await fetch('get_notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&user_id=${currentUserId}&notification_id=${notificationId}`
                });
                
                const data = await response.json();
                if (data.success) {
                    loadNotifications(); // 목록 새로고침
                }
            } catch (error) {
                console.error('알림 삭제 실패:', error);
            }
        }
        
        async function clearAllNotifications() {
            if (!confirm('모든 알림을 삭제하시겠습니까?')) return;
            
            try {
                const response = await fetch('get_notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=clear_all&user_id=${currentUserId}`
                });
                
                const data = await response.json();
                if (data.success) {
                    loadNotifications(); // 목록 새로고침
                    showToast('모든 알림이 삭제되었습니다');
                }
            } catch (error) {
                console.error('모든 알림 삭제 실패:', error);
            }
        }
        
        function updateNotificationBadge(count) {
            const badges = document.querySelectorAll('.notification-badge');
            badges.forEach(badge => {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
        }
        
        // 주기적으로 새 알림 확인
        function startNotificationCheck() {
            // 초기 확인
            checkNewNotifications();
            
            // 30초마다 확인
            notificationCheckInterval = setInterval(() => {
                checkNewNotifications();
            }, 30000);
        }
        
        async function checkNewNotifications() {
            try {
                const response = await fetch(`get_notifications.php?action=fetch&user_id=${currentUserId}&limit=1`);
                const data = await response.json();
                
                if (data.success && data.unread_count > 0) {
                    updateNotificationBadge(data.unread_count);
                    
                    // 새 알림이 있으면 알림 배너 표시
                    const updateAlert = document.getElementById('update-alert');
                    if (updateAlert && data.notifications.length > 0) {
                        const latestNotification = data.notifications[0];
                        updateAlert.querySelector('.text-yellow-300').textContent = latestNotification.message;
                        updateAlert.classList.remove('hidden');
                    }
                }
            } catch (error) {
                console.error('알림 확인 실패:', error);
            }
        }
        
        function dismissAlert() {
            document.getElementById('update-alert').classList.add('hidden');
        }
        
        // AI 비법노트 채팅 관련 함수들
        async function openAIChat() {
            document.getElementById('ai-chat-modal').classList.remove('hidden');
            
            // 채팅 초기화 (처음 열 때만)
            if (aiChatConversation.length === 0) {
                await initializeAIChat();
            }
        }
        
        function closeAIChat() {
            document.getElementById('ai-chat-modal').classList.add('hidden');
        }
        
        async function initializeAIChat() {
            // 채팅 메시지 영역 초기화
            const chatMessages = document.getElementById('ai-chat-messages');
            chatMessages.innerHTML = '';
            
            try {
                // 시험 자료 컨텍스트 초기화
                const response = await fetch('gpt_chat_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'init',
                        school: userData.school || '',
                        grade: userData.grade || 1,
                        examType: userData.examType || ''
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.config) {
                    // config 정보를 사용하여 환영 메시지 표시
                    const config = data.config;
                    
                    // 환영 메시지 추가
                    addAIChatMessage(config.greeting || '안녕하세요! AI 튜터입니다. 📚', 'ai', config.tutor_name);
                    
                    // API 설정 확인 및 안내
                    if (!config.api_configured) {
                        addAIChatMessage('⚠️ OpenAI API 키가 설정되지 않았습니다. config.php에서 API 키를 설정해주세요.', 'ai', config.tutor_name);
                    } else {
                        // 시험 자료 컨텍스트 정보 표시
                        setTimeout(() => {
                            let contextMessage = config.intro || '시험 자료를 확인했어요!';
                            
                            // 컨텍스트 정보 추가
                            if (data.context) {
                                contextMessage += '\n\n📋 현재 로드된 자료:\n' + data.context;
                            }
                            
                            // 모델 정보 표시
                            contextMessage += '\n\n🤖 사용 모델: ' + (config.model || 'GPT-3.5');
                            
                            addAIChatMessage(contextMessage, 'ai', config.tutor_name);
                        }, 500);
                    }
                } else {
                    // 기본 환영 메시지
                    addAIChatMessage('안녕하세요! AI 튜터입니다. 무엇이든 물어보세요!', 'ai');
                }
            } catch (error) {
                console.error('AI 채팅 초기화 실패:', error);
                addAIChatMessage('죄송해요. 시스템 초기화에 실패했어요. 그래도 질문해주시면 최선을 다해 답변할게요!', 'ai');
            }
        }
        
        function setAIQuestion(question) {
            document.getElementById('ai-chat-input').value = question;
            document.getElementById('ai-chat-input').focus();
        }
        
        async function sendAIMessage() {
            if (isAIProcessing) return;
            
            const input = document.getElementById('ai-chat-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            // 처리 중 플래그 설정
            isAIProcessing = true;
            
            // 사용자 메시지 추가
            addAIChatMessage(message, 'user');
            aiChatConversation.push({ role: 'user', content: message });
            
            // 입력창 초기화
            input.value = '';
            
            // 버튼 비활성화 및 로딩 표시
            const sendBtn = document.getElementById('ai-send-btn');
            const originalBtnContent = sendBtn.innerHTML;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span>생각중...</span> <span>⏳</span>';
            
            // 타이핑 인디케이터 추가
            addTypingIndicator();
            
            try {
                // GPT API 호출
                const response = await fetch('gpt_chat_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'chat',
                        school: userData.school || '',
                        grade: userData.grade || 1,
                        examType: userData.examType || '',
                        message: message,
                        conversation: aiChatConversation.slice(-10) // 최근 10개 대화만 전송
                    })
                });
                
                const data = await response.json();
                
                // 타이핑 인디케이터 제거
                removeTypingIndicator();
                
                if (data.success) {
                    // AI 응답 추가
                    addAIChatMessage(data.response, 'ai');
                    aiChatConversation.push({ role: 'assistant', content: data.response });
                } else {
                    throw new Error(data.error || '응답을 받을 수 없습니다.');
                }
            } catch (error) {
                console.error('AI 메시지 전송 실패:', error);
                console.error('상세 오류:', {
                    message: error.message,
                    stack: error.stack,
                    response: error.response
                });
                removeTypingIndicator();
                
                // 더 구체적인 오류 메시지
                let errorMessage = '죄송해요. 일시적인 문제가 발생했어요. 😥\n\n';
                if (error.message.includes('API')) {
                    errorMessage += '💡 OpenAI API 키가 설정되지 않았거나 잘못되었을 수 있습니다.\nconfig.php 파일에서 OPENAI_API_KEY를 확인해주세요.';
                } else if (error.message.includes('네트워크') || error.message.includes('Network')) {
                    errorMessage += '🌐 네트워크 연결을 확인해주세요.';
                } else {
                    errorMessage += '🔧 오류 내용: ' + error.message;
                }
                
                addAIChatMessage(errorMessage, 'ai');
            } finally {
                // 버튼 복구
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalBtnContent;
                isAIProcessing = false;
            }
        }
        
        function addAIChatMessage(message, sender, tutorName = 'AI 튜터') {
            const chatMessages = document.getElementById('ai-chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${sender} mb-4`;
            
            const currentTime = new Date().toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' });
            
            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex items-start gap-3 justify-end">
                        <div class="flex-1 text-right">
                            <div class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-2xl rounded-tr-none p-4 shadow-sm max-w-lg">
                                <p class="text-white whitespace-pre-wrap">${escapeHtml(message)}</p>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">${currentTime}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-700 font-bold">
                            나
                        </div>
                    </div>
                `;
            } else {
                // AI 메시지는 마크다운 형식 지원
                const formattedMessage = formatAIMessage(message);
                messageDiv.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                            AI
                        </div>
                        <div class="flex-1">
                            <div class="bg-white rounded-2xl rounded-tl-none p-4 shadow-sm max-w-lg">
                                <div class="text-gray-800 ai-message-content">${formattedMessage}</div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">${tutorName} · ${currentTime}</p>
                        </div>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            // 부드러운 스크롤 애니메이션
            chatMessages.scrollTo({
                top: chatMessages.scrollHeight,
                behavior: 'smooth'
            });
        }
        
        function addTypingIndicator() {
            const chatMessages = document.getElementById('ai-chat-messages');
            const indicator = document.createElement('div');
            indicator.id = 'ai-typing-indicator';
            indicator.className = 'chat-message ai mb-4';
            indicator.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                        AI
                    </div>
                    <div class="flex-1">
                        <div class="bg-white rounded-2xl rounded-tl-none p-4 shadow-sm inline-block">
                            <div class="flex gap-1">
                                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            chatMessages.appendChild(indicator);
            // 부드러운 스크롤 애니메이션
            chatMessages.scrollTo({
                top: chatMessages.scrollHeight,
                behavior: 'smooth'
            });
        }
        
        function removeTypingIndicator() {
            const indicator = document.getElementById('ai-typing-indicator');
            if (indicator) {
                indicator.remove();
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatAIMessage(message) {
            // 간단한 마크다운 처리
            let formatted = escapeHtml(message);
            
            // 줄바꿈 처리
            formatted = formatted.replace(/\n/g, '<br>');
            
            // 굵은 글씨 처리
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            // 리스트 처리
            formatted = formatted.replace(/^- (.*?)$/gm, '• $1');
            formatted = formatted.replace(/^\d+\. (.*?)$/gm, '<span class="ml-2">$&</span>');
            
            // 코드 블록 처리
            formatted = formatted.replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 rounded">$1</code>');
            
            return formatted;
        }
        
        // 시험 정보 팝업 관련 함수들
        function showExamInfo() {
            document.getElementById('exam-info-popup').classList.remove('hidden');
            // 메인 화면으로 초기화
            backToExamInfoMain();
        }

        function closeExamInfo() {
            document.getElementById('exam-info-popup').classList.add('hidden');
        }

        function backToExamInfoMain() {
            document.getElementById('exam-info-main').classList.remove('hidden');
            document.getElementById('exam-resources-list').classList.add('hidden');
            document.getElementById('exam-tips-list').classList.add('hidden');
        }

        async function showExamResources() {
            // 화면 전환
            document.getElementById('exam-info-main').classList.add('hidden');
            document.getElementById('exam-resources-list').classList.remove('hidden');
            
            // 로딩 표시
            document.getElementById('resources-loading').classList.remove('hidden');
            document.getElementById('resources-content').classList.add('hidden');
            document.getElementById('no-resources').classList.add('hidden');
            
            try {
                // API 호출
                const response = await fetch('get_exam_resources.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `school=${encodeURIComponent(userData.school)}&grade=${userData.grade}&examType=${userData.examType}`
                });
                
                const data = await response.json();
                
                // 로딩 숨기기
                document.getElementById('resources-loading').classList.add('hidden');
                
                if (data.success && data.files && data.files.length > 0) {
                    const resourcesContent = document.getElementById('resources-content');
                    resourcesContent.innerHTML = '';
                    
                    data.files.forEach((file, index) => {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-all';
                        
                        // 파일명 추출 (URL에서)
                        const fileName = file.url.split('/').pop() || `자료 ${index + 1}`;
                        
                        fileItem.innerHTML = `
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📄</span>
                                <div>
                                    <p class="font-medium text-gray-800">${fileName}</p>
                                    <p class="text-xs text-gray-500">${new Date(file.created_at).toLocaleDateString('ko-KR')}</p>
                                </div>
                            </div>
                            <a href="${file.url}" target="_blank" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-all text-sm">
                                열기
                            </a>
                        `;
                        
                        resourcesContent.appendChild(fileItem);
                    });
                    
                    resourcesContent.classList.remove('hidden');
                } else {
                    document.getElementById('no-resources').classList.remove('hidden');
                }
            } catch (error) {
                console.error('자료 로드 실패:', error);
                document.getElementById('resources-loading').classList.add('hidden');
                document.getElementById('no-resources').classList.remove('hidden');
            }
        }

        async function showExamTips() {
            // 화면 전환
            document.getElementById('exam-info-main').classList.add('hidden');
            document.getElementById('exam-tips-list').classList.remove('hidden');
            
            // 로딩 표시
            document.getElementById('tips-loading').classList.remove('hidden');
            document.getElementById('tips-content').classList.add('hidden');
            document.getElementById('no-tips').classList.add('hidden');
            
            try {
                // API 호출
                const response = await fetch('get_exam_resources.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `school=${encodeURIComponent(userData.school)}&grade=${userData.grade}&examType=${userData.examType}`
                });
                
                const data = await response.json();
                
                // 로딩 숨기기
                document.getElementById('tips-loading').classList.add('hidden');
                
                if (data.success && data.tips && data.tips.length > 0) {
                    const tipsContent = document.getElementById('tips-content');
                    tipsContent.innerHTML = '';
                    
                    data.tips.forEach((tip, index) => {
                        const tipItem = document.createElement('div');
                        tipItem.className = 'bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-200';
                        
                        tipItem.innerHTML = `
                            <div class="flex items-start gap-3">
                                <span class="text-2xl mt-1">💡</span>
                                <div class="flex-1">
                                    <p class="text-gray-800">${tip.text}</p>
                                    <p class="text-xs text-gray-500 mt-2">${new Date(tip.created_at).toLocaleDateString('ko-KR')}</p>
                                </div>
                            </div>
                        `;
                        
                        tipsContent.appendChild(tipItem);
                    });
                    
                    tipsContent.classList.remove('hidden');
                } else {
                    document.getElementById('no-tips').classList.remove('hidden');
                }
            } catch (error) {
                console.error('팁 로드 실패:', error);
                document.getElementById('tips-loading').classList.add('hidden');
                document.getElementById('no-tips').classList.remove('hidden');
            }
        }

        // 업로드 모달 열기
        function showUpload() {
            const modal = document.getElementById('upload-modal');
            if (modal) {
                modal.classList.remove('hidden');
                resetUploadModal();
            }
        }

        // 업로드 타입 선택
        function selectUploadType(type) {
            // 버튼 스타일 초기화
            document.getElementById('upload-type-file').classList.remove('border-indigo-500', 'bg-indigo-50');
            document.getElementById('upload-type-text').classList.remove('border-green-500', 'bg-green-50');
            
            // 섹션 숨기기
            document.getElementById('file-upload-section').classList.add('hidden');
            document.getElementById('text-upload-section').classList.add('hidden');
            
            // 선택된 타입에 따라 표시
            if (type === 'file') {
                document.getElementById('upload-type-file').classList.add('border-indigo-500', 'bg-indigo-50');
                document.getElementById('file-upload-section').classList.remove('hidden');
            } else if (type === 'text') {
                document.getElementById('upload-type-text').classList.add('border-green-500', 'bg-green-50');
                document.getElementById('text-upload-section').classList.remove('hidden');
            }
        }

        // 업로드 모달 닫기
        function closeUploadModal() {
            const modal = document.getElementById('upload-modal');
            if (modal) {
                modal.classList.add('hidden');
                resetUploadModal();
            }
        }

        // 모달 초기화
        function resetUploadModal() {
            // 업로드 타입 버튼 초기화
            document.getElementById('upload-type-file').classList.remove('border-indigo-500', 'bg-indigo-50');
            document.getElementById('upload-type-text').classList.remove('border-green-500', 'bg-green-50');
            
            // 섹션 숨기기
            document.getElementById('file-upload-section').classList.add('hidden');
            document.getElementById('text-upload-section').classList.add('hidden');
            
            // 파일 입력 초기화
            const fileInput = document.getElementById('exam-file-input');
            if (fileInput) fileInput.value = '';
            document.getElementById('file-preview').classList.add('hidden');
            document.getElementById('upload-file-btn').disabled = true;
            
            // 텍스트 입력 초기화
            const tipContent = document.getElementById('tip-content');
            const tipType = document.getElementById('tip-type');
            
            if (tipContent) tipContent.value = '';
            if (tipType) tipType.value = '';
            
            // 링크 입력 초기화
            const linkInput = document.getElementById('resource-link');
            const descInput = document.getElementById('resource-description');
            const linkPreview = document.getElementById('link-preview');
            
            if (linkInput) linkInput.value = '';
            if (descInput) descInput.value = '';
            if (linkPreview) linkPreview.classList.add('hidden');
            
            // 업로드 모드 초기화
            currentUploadMode = 'file';
            
            // 버튼 초기화
            document.querySelectorAll('.upload-type-btn').forEach(btn => {
                btn.className = 'upload-type-btn cursor-pointer p-6 border-2 border-gray-200 rounded-2xl hover:border-indigo-300 hover:bg-indigo-50 transition-all';
            });
        }


        // 유형 선택으로 돌아가기
        function backToUploadType() {
            const stepFile = document.getElementById('upload-step-file');
            const stepText = document.getElementById('upload-step-text');
            const step1 = document.getElementById('upload-step-1');
            
            if (stepFile) stepFile.classList.add('hidden');
            if (stepText) stepText.classList.add('hidden');
            if (step1) step1.classList.remove('hidden');
            
            currentUploadType = '';
        }

        // 파일 선택 처리
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                // 파일 크기 체크 (10MB 제한)
                if (file.size > 10 * 1024 * 1024) {
                    showToast('파일 크기는 10MB 이하여야 합니다.', 'error');
                    event.target.value = '';
                    return;
                }
                
                // 파일 미리보기 표시
                document.getElementById('selected-file-name').textContent = file.name;
                document.getElementById('file-preview').classList.remove('hidden');
                document.getElementById('upload-file-btn').disabled = false;
            }
        }

        // 드래그 앤 드롭 관련 함수들
        function handleDragOver(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
        }

        function handleDragEnter(event) {
            event.preventDefault();
            const dropZone = document.getElementById('file-drop-zone');
            dropZone.className = 'border-2 border-dashed border-indigo-500 bg-indigo-50 rounded-2xl p-8 text-center transition-all';
        }

        function handleDragLeave(event) {
            event.preventDefault();
            // 드롭존을 완전히 벗어났을 때만 스타일 초기화
            if (!event.currentTarget.contains(event.relatedTarget)) {
                const dropZone = document.getElementById('file-drop-zone');
                dropZone.className = 'border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:border-indigo-300 transition-all';
            }
        }

        function handleFileDrop(event) {
            event.preventDefault();
            const dropZone = document.getElementById('file-drop-zone');
            dropZone.className = 'border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center hover:border-indigo-300 transition-all';
            
            const files = Array.from(event.dataTransfer.files);
            processFiles(files);
        }

        // 파일 처리 공통 함수
        function processFiles(files) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt'];
            
            files.forEach(file => {
                // 파일 크기 체크
                if (file.size > maxSize) {
                    showToast(`파일이 너무 큽니다: ${file.name} (최대 10MB)`, 'error');
                    return;
                }
                
                // 파일 형식 체크
                const fileExt = file.name.split('.').pop().toLowerCase();
                if (!allowedTypes.includes(fileExt)) {
                    showToast(`지원하지 않는 파일 형식: ${file.name}`, 'error');
                    return;
                }
                
                // 중복 파일 체크
                if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                }
            });
            
            updateFileList();
        }

        // 파일 목록 업데이트
        function updateFileList() {
            const fileList = document.getElementById('file-list');
            const fileItems = document.getElementById('file-items');
            const uploadBtn = document.getElementById('upload-files-btn');
            
            if (selectedFiles.length === 0) {
                fileList.classList.add('hidden');
                uploadBtn.disabled = true;
                return;
            }
            
            fileList.classList.remove('hidden');
            uploadBtn.disabled = false;
            
            fileItems.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-xl';
                
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const fileIcon = getFileIcon(file.name);
                
                fileItem.innerHTML = `
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">${fileIcon}</span>
                        <div>
                            <div class="font-medium text-sm">${file.name}</div>
                            <div class="text-xs text-gray-500">${fileSize} MB</div>
                        </div>
                    </div>
                    <button onclick="removeFile(${index})" class="text-red-500 hover:text-red-700 text-xl">
                        🗑️
                    </button>
                `;
                
                fileItems.appendChild(fileItem);
            });
        }

        // 파일 아이콘 반환
        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const icons = {
                'pdf': '📄',
                'doc': '📝', 'docx': '📝',
                'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️',
                'txt': '📄'
            };
            return icons[ext] || '📎';
        }

        // 파일 제거
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileList();
        }

        // 업로드 모드 변수
        let currentUploadMode = 'file'; // 'file' 또는 'link'

        // 업로드 모드 전환
        function switchUploadMode(mode) {
            currentUploadMode = mode;
            const fileTab = document.getElementById('file-tab');
            const linkTab = document.getElementById('link-tab');
            const fileArea = document.getElementById('file-upload-area');
            const linkArea = document.getElementById('link-upload-area');
            const uploadBtn = document.getElementById('upload-resources-btn');
            const btnText = document.getElementById('upload-btn-text');

            // 필수 요소들이 존재하는지 확인
            if (!fileTab || !linkTab || !fileArea || !linkArea || !uploadBtn || !btnText) {
                console.error('업로드 모달 요소를 찾을 수 없습니다');
                return;
            }

            if (mode === 'file') {
                // 파일 모드 활성화
                fileTab.className = 'flex-1 py-2 px-4 rounded-lg transition-all bg-white text-indigo-600 shadow-sm font-medium';
                linkTab.className = 'flex-1 py-2 px-4 rounded-lg transition-all text-gray-600 hover:text-gray-800';
                fileArea.classList.remove('hidden');
                linkArea.classList.add('hidden');
                btnText.textContent = '📤 파일 업로드하기';
                
                // 파일 선택 여부에 따라 버튼 활성화
                uploadBtn.disabled = selectedFiles.length === 0;
            } else {
                // 링크 모드 활성화
                linkTab.className = 'flex-1 py-2 px-4 rounded-lg transition-all bg-white text-indigo-600 shadow-sm font-medium';
                fileTab.className = 'flex-1 py-2 px-4 rounded-lg transition-all text-gray-600 hover:text-gray-800';
                linkArea.classList.remove('hidden');
                fileArea.classList.add('hidden');
                btnText.textContent = '🔗 링크 업로드하기';
                
                // 링크 입력 여부에 따라 버튼 활성화
                const linkInput = document.getElementById('resource-link');
                uploadBtn.disabled = !linkInput || !linkInput.value.trim();
            }
        }

        // 통합 업로드 함수
        async function uploadResources() {
            if (currentUploadMode === 'file') {
                await uploadFiles();
            } else {
                await uploadLink();
            }
        }

        // 파일 업로드 (기존 함수 수정)
        async function uploadFiles() {
            if (selectedFiles.length === 0) {
                showToast('업로드할 파일을 선택해주세요', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('upload_type', 'file');
            formData.append('userid', userData.userid);
            formData.append('school', userData.school);
            formData.append('grade', userData.grade);
            formData.append('examType', userData.examType);
            
            selectedFiles.forEach((file, index) => {
                formData.append(`files[]`, file);
            });

            try {
                const uploadBtn = document.getElementById('upload-resources-btn');
                const btnText = document.getElementById('upload-btn-text');
                
                if (uploadBtn && btnText) {
                    uploadBtn.disabled = true;
                    btnText.textContent = '📤 업로드 중...';
                }

                const response = await fetch('upload_exam_resources.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast(`${selectedFiles.length}개 파일 업로드 완료!`);
                    closeUploadModal();
                } else {
                    showToast('파일 업로드 실패: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('File upload error:', error);
                showToast('업로드 중 오류 발생: ' + error.message, 'error');
            } finally {
                const uploadBtn = document.getElementById('upload-resources-btn');
                const btnText = document.getElementById('upload-btn-text');
                
                if (uploadBtn && btnText) {
                    uploadBtn.disabled = false;
                    btnText.textContent = '📤 파일 업로드하기';
                }
            }
        }

        // 링크 업로드
        async function uploadLink() {
            const linkInput = document.getElementById('resource-link');
            const descriptionInput = document.getElementById('resource-description');
            
            if (!linkInput || !descriptionInput) {
                showToast('페이지 오류: 입력 요소를 찾을 수 없습니다', 'error');
                return;
            }
            
            const link = linkInput.value.trim();
            const description = descriptionInput.value.trim();

            if (!link) {
                showToast('자료 링크를 입력해주세요', 'error');
                return;
            }

            // URL 유효성 검사
            try {
                new URL(link);
            } catch (e) {
                showToast('올바른 URL 형식을 입력해주세요', 'error');
                return;
            }

            const data = {
                upload_type: 'link',
                userid: userData.userid,
                school: userData.school,
                grade: userData.grade,
                examType: userData.examType,
                resource_link: link,
                resource_description: description
            };

            try {
                const uploadBtn = document.getElementById('upload-resources-btn');
                const btnText = document.getElementById('upload-btn-text');
                
                if (uploadBtn && btnText) {
                    uploadBtn.disabled = true;
                    btnText.textContent = '🔗 업로드 중...';
                }

                const response = await fetch('upload_exam_resources.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('링크 업로드 완료!');
                    closeUploadModal();
                } else {
                    showToast('링크 업로드 실패: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Link upload error:', error);
                showToast('업로드 중 오류 발생: ' + error.message, 'error');
            } finally {
                const uploadBtn = document.getElementById('upload-resources-btn');
                const btnText = document.getElementById('upload-btn-text');
                
                if (uploadBtn && btnText) {
                    uploadBtn.disabled = false;
                    btnText.textContent = '🔗 링크 업로드하기';
                }
            }
        }

        // 파일 업로드
        async function uploadFile() {
            const fileInput = document.getElementById('exam-file-input');
            const file = fileInput.files[0];
            
            if (!file) {
                showToast('파일을 선택해주세요.', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('upload_type', 'file');
            formData.append('file', file);
            formData.append('userid', userData.userid);
            formData.append('school', userData.school);
            formData.append('grade', userData.grade);
            formData.append('examType', userData.examType);
            
            try {
                const uploadBtn = document.getElementById('upload-file-btn');
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '📁 업로드 중...';
                
                const response = await fetch('upload_exam_resources.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('파일 업로드 완료!');
                    closeUploadModal();
                } else {
                    showToast('파일 업로드 실패: ' + result.message, 'error');
                }
            } catch (error) {
                showToast('업로드 중 오류 발생: ' + error.message, 'error');
            } finally {
                const uploadBtn = document.getElementById('upload-file-btn');
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '📁 파일 업로드하기';
            }
        }

        // 텍스트 정보 업로드
        async function uploadTip() {
            const tipType = document.getElementById('tip-type').value;
            const tipContent = document.getElementById('tip-content').value.trim();

            if (!tipContent) {
                showToast('내용을 입력해주세요', 'error');
                return;
            }

            const data = {
                upload_type: 'text',
                userid: userData.userid,
                school: userData.school,
                grade: userData.grade,
                examType: userData.examType,
                tip_type: tipType,
                tip_content: tipContent
            };

            try {
                const uploadBtn = document.getElementById('upload-tip-btn');
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '💡 업로드 중...';

                const response = await fetch('upload_exam_resources.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('정보 업로드 완료!');
                    closeUploadModal();
                } else {
                    showToast('정보 업로드 실패: ' + result.message, 'error');
                }
            } catch (error) {
                showToast('업로드 중 오류 발생: ' + error.message, 'error');
            } finally {
                const uploadBtn = document.getElementById('upload-tip-btn');
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '💡 정보 업로드하기';
            }
        }

        // 대시보드 정보 업데이트
        function updateDashboardInfo() {
            console.log('=== updateDashboardInfo 호출됨 ===');
            console.log('Dashboard userData:', userData);
            console.log('Dashboard examPeriod:', examPeriod);
            console.log('Dashboard examScope:', examScope);
            
            // PHP에서 직접 전달받은 데이터도 확인
            console.log('phpUserData.examStartDate:', phpUserData.examStartDate);
            console.log('phpUserData.examEndDate:', phpUserData.examEndDate);
            console.log('phpUserData.mathExamDate:', phpUserData.mathExamDate);
            console.log('phpUserData.examScope:', phpUserData.examScope);
            
            // 프로필 정보 업데이트
            const profileSchool = document.getElementById('profile-school');
            const profileGrade = document.getElementById('profile-grade');
            const profileExam = document.getElementById('profile-exam');
            
            console.log('대시보드 요소 확인:');
            console.log('profile-school 요소:', profileSchool);
            console.log('profile-grade 요소:', profileGrade);
            console.log('profile-exam 요소:', profileExam);
            
            if (profileSchool) {
                profileSchool.textContent = userData.school || '학교명';
                console.log('profile-school 업데이트:', userData.school);
            }
            if (profileGrade) {
                profileGrade.textContent = userData.grade ? `${userData.grade}학년` : '학년';
                console.log('profile-grade 업데이트:', userData.grade);
            }
            
            // 시험 종류 업데이트
            if (userData.examType && profileExam) {
                const examType = examTypes.find(e => e.id === userData.examType);
                profileExam.textContent = examType ? examType.name : '시험 종류';
            }
            
            // 시험 범위 업데이트 (스크롤뷰와 탭뷰 모두)
            const dashboardScope = document.getElementById('dashboard-scope-scroll');
            const tabScope = document.getElementById('tab-scope');
            const scopeContent = examScope.content || '범위 미입력';
            
            if (dashboardScope) {
                dashboardScope.textContent = scopeContent;
            }
            if (tabScope) {
                tabScope.textContent = scopeContent;
            }
            
            // 시험 기간 표시 업데이트 (스크롤뷰와 탭뷰 모두)
            const examElements = [
                {start: 'exam-start-display', end: 'exam-end-display'},
                {start: 'exam-start-display-scroll', end: 'exam-end-display-scroll'},
                {period: 'tab-period'}
            ];
            
            examElements.forEach(elem => {
                if (elem.start && elem.end) {
                    const startEl = document.getElementById(elem.start);
                    const endEl = document.getElementById(elem.end);
                    if (startEl && endEl) {
                        if (examPeriod.start && examPeriod.end) {
                            startEl.textContent = formatDate(examPeriod.start);
                            endEl.textContent = formatDate(examPeriod.end);
                        } else {
                            startEl.textContent = '-';
                            endEl.textContent = '-';
                        }
                    }
                }
                if (elem.period) {
                    const periodEl = document.getElementById(elem.period);
                    if (periodEl && examPeriod.start && examPeriod.end) {
                        periodEl.textContent = `${formatDate(examPeriod.start)} ~ ${formatDate(examPeriod.end)}`;
                    }
                }
            });
            
            // D-Day 업데이트 (스크롤뷰와 탭뷰 모두)
            const ddayElements = [
                document.getElementById('dashboard-dday'),
                document.getElementById('tab-dday')
            ];
            
            let ddayText = 'D-?';
            if (examPeriod.mathDate) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const mathDate = new Date(examPeriod.mathDate);
                mathDate.setHours(0, 0, 0, 0);
                const diffTime = mathDate - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays > 0) {
                    ddayText = `D-${diffDays}`;
                } else if (diffDays === 0) {
                    ddayText = 'D-DAY';
                } else {
                    ddayText = `D+${Math.abs(diffDays)}`;
                }
            }
            
            ddayElements.forEach(el => {
                if (el) el.textContent = ddayText;
            });
            
            // 예상/확정 상태 업데이트
            const statusElements = document.querySelectorAll('.bg-yellow-500\\/20');
            statusElements.forEach(element => {
                if (examScope.status === 'confirmed') {
                    element.className = element.className.replace('bg-yellow-500/20 text-yellow-300', 'bg-green-500/20 text-green-300');
                    element.textContent = '확정';
                } else {
                    element.textContent = '예상';
                }
            });
            
            // 시험 기간 카드 업데이트 - 별도의 dashboard-exam-period 요소는 없음
            // exam-start-display와 exam-end-display 요소는 위에서 이미 업데이트됨
            
            // 탭 모드 데이터 업데이트
            updateTabModeData();
            
            // 목표 정보 로드
            loadUserGoals();
            
            // 학습 대시보드 데이터 표시
            displayDashboardGoals();
            
            // info_time.php의 학생 정보 로드
            loadStudentInfo();
            
            // 학습 단계 선택 상태 업데이트
            updateDashboardPhaseSelection();
            
            // LMS 데이터 로드 및 표시
            loadLMSDataForDashboard();
        }
        
        // 대시보드 학습 단계 선택 상태 업데이트
        function updateDashboardPhaseSelection() {
            // 모든 phase-item 초기화
            document.querySelectorAll('.phase-item').forEach(item => {
                item.classList.remove('ring-2', 'ring-purple-400', 'ring-offset-2', 'ring-offset-slate-900');
            });
            
            // 현재 선택된 학습 단계 강조
            const phaseItems = document.querySelectorAll('.phase-item');
            phaseItems.forEach(item => {
                const onclickAttr = item.getAttribute('onclick');
                if (onclickAttr && onclickAttr.includes(`'${studyPhase}'`)) {
                    item.classList.add('ring-2', 'ring-purple-400', 'ring-offset-2', 'ring-offset-slate-900');
                }
            });
        }
        
        // 날짜 포맷 함수
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return `${date.getMonth() + 1}/${date.getDate()}`;
        }
        
        // 목표 관리 페이지 열기
        function openGoalsPage() {
            window.open(`info_goal.php?id=${userData.userid}`, '_blank');
        }
        
        // info_time.php의 학생 정보 로드
        async function loadStudentInfo() {
            try {
                const response = await fetch(`get_student_info.php?userid=${userData.userid}`);
                const result = await response.json();
                
                if (result.success) {
                    // 오늘의 학습 통계 업데이트
                    if (result.today_stats) {
                        const hours = Math.floor(result.today_stats.total_duration_minutes / 60);
                        const minutes = result.today_stats.total_duration_minutes % 60;
                        document.getElementById('study-time').textContent = `${hours}시간 ${minutes}분`;
                        document.getElementById('completed-activities').textContent = `${result.today_stats.completed_activities}개`;
                        
                        // 만족도 별 표시
                        const satisfactionLevel = Math.round(result.today_stats.average_satisfaction);
                        const stars = document.getElementById('satisfaction-stars');
                        if (stars) {
                            let starsHTML = '';
                            for (let i = 1; i <= 5; i++) {
                                if (i <= satisfactionLevel) {
                                    starsHTML += '<div class="w-4 h-4 bg-yellow-500 rounded-sm"></div>';
                                } else {
                                    starsHTML += '<div class="w-4 h-4 bg-purple-500/30 rounded-sm"></div>';
                                }
                            }
                            stars.innerHTML = starsHTML;
                        }
                    }
                    
                    // 휴식 시간 정보
                    if (result.break_info) {
                        const breakInfo = document.getElementById('break-time-info');
                        if (breakInfo) {
                            if (result.break_info.minutes_until_break > 0) {
                                breakInfo.textContent = `(${Math.round(result.break_info.minutes_until_break)}분 후)`;
                            } else {
                                breakInfo.textContent = '(휴식 가능)';
                                breakInfo.classList.add('text-green-300');
                            }
                        }
                    }
                    
                    // 대기 중인 활동
                    if (result.waiting_activities && result.waiting_activities.length > 0) {
                        const section = document.getElementById('waiting-activities-section');
                        const list = document.getElementById('waiting-activities-list');
                        
                        if (section && list) {
                            section.style.display = 'block';
                            list.innerHTML = '';
                            
                            result.waiting_activities.forEach(activity => {
                                const activityDiv = document.createElement('div');
                                activityDiv.className = 'bg-white/5 rounded-lg p-3 hover:bg-white/10 transition-colors cursor-pointer';
                                
                                let icon = '📚';
                                if (activity.text.includes('개념')) icon = '🌱';
                                else if (activity.text.includes('유형')) icon = '🍎';
                                else if (activity.text.includes('오답')) icon = '📝';
                                else if (activity.text.includes('시험')) icon = '🏬';
                                
                                activityDiv.innerHTML = `
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">${icon}</span>
                                        <span class="text-sm text-white">${activity.text}</span>
                                    </div>
                                `;
                                
                                list.appendChild(activityDiv);
                            });
                        }
                    }
                    
                    // 학습 맥락 정보 (콘솔에만 표시)
                    if (result.learning_context) {
                        console.log('학습 맥락:', result.learning_context);
                    }
                    
                    // 주간 목표 업데이트 (이미 다른 곳에서 처리되지만 백업용)
                    if (result.weekly_goal && !document.getElementById('weekly-goal').textContent) {
                        document.getElementById('weekly-goal').textContent = result.weekly_goal;
                    }
                    
                } else {
                    console.error('학생 정보 로드 실패:', result.error);
                }
            } catch (error) {
                console.error('학생 정보 로드 중 오류:', error);
            }
        }
        
        // 사용자 목표 정보 가져오기
        async function loadUserGoals() {
            console.log('=== loadUserGoals 시작 ===');
            console.log('사용자 ID:', userData.userid);
            
            try {
                const response = await fetch(`get_user_goals.php?userid=${userData.userid}`);
                console.log('목표 API 응답 상태:', response.status);
                
                const result = await response.json();
                console.log('목표 API 응답 데이터:', result);
                
                if (result.success) {
                    console.log('목표 데이터 로드 성공');
                    
                    // 오늘의 목표 (스크롤뷰와 탭뷰 모두 업데이트)
                    const updateGoalElements = (baseId, value, defaultText) => {
                        const elements = [
                            document.getElementById(baseId),
                            document.getElementById(`${baseId}-scroll`),
                            document.getElementById(`tab-${baseId}`)
                        ];
                        
                        elements.forEach(el => {
                            if (el) {
                                el.textContent = value || defaultText;
                                console.log(`${el.id} 업데이트:`, value || defaultText);
                            }
                        });
                    };
                    
                    // 목표들 업데이트
                    updateGoalElements('daily-goal', result.goals.today, '오늘의 목표를 설정해주세요');
                    updateGoalElements('weekly-goal', result.goals.weekly, '주간 목표를 설정해주세요');
                    updateGoalElements('quarter-goal', result.goals.quarter, '분기 목표를 설정해주세요');
                    
                    // 분기 목표 상세 정보 툴팁 설정
                    if (result.goals.quarter && result.goals.quarterDetails) {
                        const quarterElements = [
                            document.getElementById('quarter-goal'),
                            document.getElementById('quarter-goal-scroll'),
                            document.getElementById('tab-quarter-goal')
                        ];
                        
                        quarterElements.forEach(el => {
                            if (el && result.goals.quarterDetails.deadline) {
                                el.title = `마감일: ${result.goals.quarterDetails.deadline}`;
                            }
                        });
                    }
                    
                    console.log('모든 목표 요소 업데이트 완료');
                } else {
                    console.error('목표 정보 로드 실패:', result.error || result);
                    
                    // 실패시 기본 메시지 표시
                    const updateErrorElements = (baseId, errorText) => {
                        const elements = [
                            document.getElementById(baseId),
                            document.getElementById(`${baseId}-scroll`),
                            document.getElementById(`tab-${baseId}`)
                        ];
                        
                        elements.forEach(el => {
                            if (el) {
                                el.textContent = errorText;
                            }
                        });
                    };
                    
                    updateErrorElements('daily-goal', '목표를 불러올 수 없습니다');
                    updateErrorElements('weekly-goal', '목표를 불러올 수 없습니다');
                    updateErrorElements('quarter-goal', '목표를 불러올 수 없습니다');
                }
            } catch (error) {
                console.error('목표 정보 로드 중 오류:', error);
                
                // 오류 시 기본 메시지 표시
                const updateErrorElements = (baseId, errorText) => {
                    const elements = [
                        document.getElementById(baseId),
                        document.getElementById(`${baseId}-scroll`),
                        document.getElementById(`tab-${baseId}`)
                    ];
                    
                    elements.forEach(el => {
                        if (el) {
                            el.textContent = errorText;
                        }
                    });
                };
                
                updateErrorElements('daily-goal', '목표를 불러올 수 없습니다');
                updateErrorElements('weekly-goal', '목표를 불러올 수 없습니다');
                updateErrorElements('quarter-goal', '목표를 불러올 수 없습니다');
            }
            
            console.log('=== loadUserGoals 완료 ===');
        }
        
        // 학습 대시보드 데이터 표시 함수
        function displayDashboardGoals() {
            console.log('Dashboard Goals Data:', dashboardGoalsData); // 디버깅용
            
            if (!dashboardGoalsData || !dashboardGoalsData.success) {
                console.error('대시보드 데이터 로드 실패:', dashboardGoalsData);
                // 데이터가 없어도 기본 메시지 표시
                const todayGoalDetail = document.getElementById('today-goal-detail');
                const weeklyGoalDetail = document.getElementById('weekly-goal-detail');
                if (todayGoalDetail) todayGoalDetail.textContent = '오늘 목표가 설정되지 않았습니다.';
                if (weeklyGoalDetail) weeklyGoalDetail.textContent = '주간 목표가 설정되지 않았습니다.';
                return;
            }
            
            // 목표 데이터 업데이트 (스크롤뷰와 탭뷰 모두)
            const updateGoalElements = (baseId, value) => {
                const elements = [
                    document.getElementById(baseId),
                    document.getElementById(`${baseId}-scroll`),
                    document.getElementById(`tab-${baseId}`)
                ];
                elements.forEach(el => {
                    if (el) el.textContent = value;
                });
            };
            
            // 1. 오늘 목표
            const todayGoalText = dashboardGoalsData.today_goal?.text || '오늘 목표가 설정되지 않았습니다.';
            updateGoalElements('daily-goal', todayGoalText);
            
            // 2. 주간 목표
            const weeklyGoalText = dashboardGoalsData.weekly_goal?.text || '주간 목표가 설정되지 않았습니다.';
            updateGoalElements('weekly-goal', weeklyGoalText);
            
            // 3. 분기 목표
            const quarterGoals = Object.values(dashboardGoalsData.quarter_goals || {});
            const quarterGoalText = quarterGoals.length > 0 ? quarterGoals[0].title : '분기 목표가 설정되지 않았습니다.';
            updateGoalElements('quarter-goal', quarterGoalText);
            
            // 3. 최근 목표 기록 요약
            const recentGoalsSummary = document.getElementById('recent-goals-summary');
            if (recentGoalsSummary && dashboardGoalsData.recent_goals) {
                recentGoalsSummary.innerHTML = '';
                const goals = Object.values(dashboardGoalsData.recent_goals);
                if (goals.length > 0) {
                    goals.slice(0, 5).forEach(goal => {
                        const date = new Date(goal.timecreated * 1000);
                        const dateStr = `${date.getMonth() + 1}/${date.getDate()}`;
                        const typeIcon = goal.type === '오늘목표' ? '📌' : '📅';
                        const typeColor = goal.type === '오늘목표' ? 'text-yellow-300' : 'text-green-300';
                        
                        recentGoalsSummary.innerHTML += `
                            <div class="flex items-start gap-2 p-2 rounded-lg hover:bg-white/5 transition-colors">
                                <span class="text-sm">${typeIcon}</span>
                                <span class="${typeColor} text-sm font-medium min-w-[3rem]">${dateStr}</span>
                                <span class="text-purple-200 text-sm truncate flex-1">${goal.text}</span>
                            </div>
                        `;
                    });
                } else {
                    recentGoalsSummary.innerHTML = '<p class="text-xs text-purple-300">최근 목표 기록이 없습니다.</p>';
                }
            }
            
            // 4. 퀴즈 성취도 데이터
            if (dashboardGoalsData.quiz_data) {
                const quizData = dashboardGoalsData.quiz_data;
                console.log('퀴즈 데이터:', quizData); // 디버깅
                
                const quizTotal = document.getElementById('quiz-total');
                const quizToday = document.getElementById('quiz-today');
                const quizTodayAvg = document.getElementById('quiz-today-avg');
                const quizWeeklyAvg = document.getElementById('quiz-weekly-avg');
                
                // 퀴즈 데이터 업데이트 (스크롤뷰와 탭뷰 모두)
                const updateQuizElements = (baseId, value) => {
                    const elements = [
                        document.getElementById(baseId),
                        document.getElementById(`${baseId}-scroll`)
                    ];
                    elements.forEach(el => {
                        if (el) el.textContent = value;
                    });
                };
                
                updateQuizElements('quiz-total', quizData.total_count || 0);
                updateQuizElements('quiz-today', quizData.today_count || 0);
                updateQuizElements('quiz-today-score', quizData.today_average ? `${quizData.today_average}%` : '0%');
                updateQuizElements('quiz-weekly-avg', quizData.weekly_average ? `${quizData.weekly_average}%` : '0%');
            } else {
                console.log('퀴즈 데이터가 없습니다'); // 디버깅
            }
            
            // 5. 로드맵 미션 (분기목표)
            const roadmapMissions = document.getElementById('roadmap-missions');
            if (roadmapMissions && dashboardGoalsData.quarter_goals) {
                roadmapMissions.innerHTML = '';
                const missions = Object.values(dashboardGoalsData.quarter_goals);
                if (missions.length > 0) {
                    missions.forEach(mission => {
                        const ddayClass = mission.dday < 7 ? 'text-red-400' : mission.dday < 30 ? 'text-yellow-400' : 'text-green-400';
                        const typeIcon = mission.plantype === '분기목표' ? '🎯' : '🧭';
                        
                        roadmapMissions.innerHTML += `
                            <div class="bg-gradient-to-r from-indigo-500/10 to-purple-500/10 rounded-lg p-3 border border-indigo-500/20">
                                <div class="flex items-start gap-2">
                                    <span class="text-lg">${typeIcon}</span>
                                    <div class="flex-1">
                                        <p class="text-white font-medium text-sm">${mission.title || '제목 없음'}</p>
                                        <p class="text-xs text-purple-300 mt-1 line-clamp-1">${mission.memo || ''}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <span class="text-xs font-bold ${ddayClass}">D-${mission.dday || '?'}</span>
                                            ${mission.dreamchallenge ? `<span class="text-xs text-blue-300">🌟 ${mission.dreamchallenge}</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    roadmapMissions.innerHTML = '<div class="col-span-full text-center py-8"><p class="text-lg text-purple-300">설정된 로드맵이 없습니다.</p></div>';
                }
            }
            
            // 6. 내신 테스트 목록
            const internalTests = document.getElementById('internal-tests');
            if (internalTests && dashboardGoalsData.quiz_data && dashboardGoalsData.quiz_data.internal_tests) {
                internalTests.innerHTML = '';
                const tests = dashboardGoalsData.quiz_data.internal_tests;
                if (tests.length > 0) {
                    tests.forEach(test => {
                        const scoreClass = test.score >= 90 ? 'text-green-400' : test.score >= 70 ? 'text-yellow-400' : 'text-red-400';
                        internalTests.innerHTML += `
                            <div class="bg-white/5 rounded-lg p-2 hover:bg-white/10 transition-colors">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <p class="text-white text-sm truncate">${test.name}</p>
                                        <p class="text-xs text-purple-300">${test.date}</p>
                                    </div>
                                    <p class="${scoreClass} text-lg font-bold">${test.score}%</p>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    internalTests.innerHTML = '<p class="text-xs text-purple-300 text-center">최근 내신 테스트가 없습니다.</p>';
                }
            }
            
            // 7. 표준 테스트 목록
            const standardTests = document.getElementById('standard-tests');
            if (standardTests && dashboardGoalsData.quiz_data && dashboardGoalsData.quiz_data.standard_tests) {
                standardTests.innerHTML = '';
                const tests = dashboardGoalsData.quiz_data.standard_tests;
                if (tests.length > 0) {
                    tests.forEach(test => {
                        const scoreClass = test.score >= 90 ? 'text-green-400' : test.score >= 70 ? 'text-yellow-400' : 'text-red-400';
                        standardTests.innerHTML += `
                            <div class="bg-white/5 rounded-lg p-2 hover:bg-white/10 transition-colors">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <p class="text-white text-sm truncate">${test.name}</p>
                                        <p class="text-xs text-purple-300">${test.date}</p>
                                    </div>
                                    <p class="${scoreClass} text-lg font-bold">${test.score}%</p>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    standardTests.innerHTML = '<p class="text-xs text-purple-300 text-center">최근 표준 테스트가 없습니다.</p>';
                }
            }
        }

        // ==== 섹션별 DB 저장 함수들 ====
        
        // Section 0: 기본 정보 저장 (학교명, 학년, 시험종류)
        async function saveBasicInfo() {
            const data = {
                section: 0,
                userid: userData.userid,
                school: userData.school,
                grade: userData.grade,
                examType: userData.examType
            };

            console.log('saveBasicInfo 호출됨:', data);

            try {
                const response = await fetch("save_exam_data_alt42t.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });
                
                // 먼저 텍스트로 받아서 확인
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON 파싱 오류:', parseError);
                    console.error('응답 내용:', responseText);
                    
                    // HTML 응답인 경우 에러 메시지 추출 시도
                    if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                        showToast("세션이 만료되었습니다. 페이지를 새로고침해주세요.", "error");
                        return;
                    }
                    
                    showToast("서버 응답 오류: " + responseText.substring(0, 100), "error");
                    return;
                }
                
                console.log('저장 응답:', result);
                
                // 디버그 정보 출력
                if (result.debug) {
                    console.log('디버그 정보:', result.debug);
                }
                
                if (result.success) {
                    showToast("기본 정보 저장 완료!");
                } else {
                    showToast("저장 실패: " + result.message, "error");
                }
            } catch (error) {
                console.error('저장 오류:', error);
                showToast("저장 중 오류 발생: " + error.message, "error");
            }
        }

        // Section 1: 시험 일정 저장 (시작일, 종료일, 수학시험일, 상태, 범위)
        async function saveExamSchedule() {
            const data = {
                section: 1,
                userid: userData.userid,
                startDate: examPeriod.start,
                endDate: examPeriod.end,
                mathDate: examPeriod.mathDate,
                status: examPeriod.status,
                examScope: examScope.content || '' // 시험 범위 추가
            };

            try {
                console.log('Section 1 전송 데이터:', data);
                const response = await fetch("save_exam_data_alt42t.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                console.log('Section 1 응답:', result);
                if (result.success) {
                    showToast("시험 일정 저장 완료!");
                } else {
                    console.error('저장 실패 상세:', result);
                    if (result.debuginfo) {
                        console.error('디버그 정보:', result.debuginfo);
                    }
                    showToast("저장 실패: " + result.message, "error");
                }
            } catch (error) {
                showToast("저장 중 오류 발생: " + error.message, "error");
            }
        }

        // 예상/확정 상태 업데이트 함수
        function updateExamStatus(status) {
            examPeriod.status = status;
            examScope.status = status;
            console.log('시험 상태 업데이트:', status);
            
            // 상태 배지 업데이트
            const statusBadges = ['exam-start-status', 'exam-end-status', 'math-date-status'];
            statusBadges.forEach(badgeId => {
                const badge = document.getElementById(badgeId);
                if (badge) {
                    if (status === 'confirmed') {
                        badge.className = 'text-xs px-2 py-1 rounded bg-green-100 text-green-700';
                        badge.textContent = '확정';
                    } else {
                        badge.className = 'text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-700';
                        badge.textContent = '예상';
                    }
                }
            });
        }

        // Section 2: 학습 상태 저장 (개념공부/개념복습/유형공부)
        async function saveStudyStatus() {
            // studyPhase를 한글로 변환
            const studyStatusMap = {
                'concept': '개념공부',
                'concept-review': '개념복습', 
                'type-study': '유형공부'
            };
            
            const data = {
                section: 3,
                userid: userData.userid,
                studyStatus: studyStatusMap[studyPhase] || studyPhase
            };

            try {
                console.log('Section 3 전송 데이터:', data);
                console.log('현재 studyPhase:', studyPhase);
                const response = await fetch("save_exam_data_alt42t.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                console.log('Section 3 응답:', result);
                if (result.success) {
                    showToast("학습 단계 저장 완료!");
                } else {
                    showToast("저장 실패: " + result.message, "error");
                }
            } catch (error) {
                showToast("저장 중 오류 발생: " + error.message, "error");
            }
        }

        // === completeSection 수정 ===
        function completeSection(sectionIndex) {
            if (!completedSections.includes(sectionIndex)) {
                completedSections.push(sectionIndex);
                updateProgress();
                updateNavigation();
            }

            // 각 섹션별 DB 저장
            console.log('completeSection 호출됨, sectionIndex:', sectionIndex);
            
            if (sectionIndex === 0) {
                // 정보입력 완료: 학교명, 학년, 시험종류 저장
                console.log('Section 0 저장 시작');
                saveBasicInfo();
                // Section 0 완료 후 친구 정보 로드 (다음이 Section 1이므로)
                setTimeout(() => {
                    loadExamFriends();
                }, 1000);
            } else if (sectionIndex === 1) {
                // 시험설정 완료: 시험 일정 저장
                console.log('Section 1 저장 시작');
                saveExamSchedule();
            } else if (sectionIndex === 3) {
                // 학습 시작점 선택 완료: 학습 상태 저장
                console.log('Section 3 저장 시작');
                saveStudyStatus();
            }

            if (sectionIndex < 4) {
                currentSection = sectionIndex + 1;
                setTimeout(() => {
                    goToSection(sectionIndex + 1);
                    
                    // Section 1 진입 시 친구 정보 로드 및 기본 날짜 설정
                    if (sectionIndex + 1 === 1) {
                        loadExamFriends();
                        // 저장된 날짜가 없으면 기본 날짜 설정
                        if (!examPeriod.start && !examPeriod.end && !examPeriod.mathDate) {
                            setDefaultExamDates();
                        }
                    }
                    
                    if (sectionIndex + 1 === 4) {
                        updateDashboardInfo();
                        // 대시보드 데이터 강제 새로고침
                        setTimeout(() => {
                            displayDashboardGoals();
                        }, 100);
                        // 대시보드 진입 시 알림 확인 시작
                        startNotificationCheck();
                    }
                    startTypingEffect();
                }, 300);
            }
        }
    </script>

    <!-- AI 비법노트 채팅 모달 -->
    <div id="ai-chat-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl" style="width: min(900px, 90vw); height: min(600px, 80vh); display: flex; flex-direction: column;">
            <!-- 모달 헤더 -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 text-white rounded-t-2xl flex-shrink-0">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center gap-2">
                            <span>🤖</span>
                            AI 비법노트
                        </h2>
                        <p class="text-purple-100 text-sm mt-1">시험 자료를 학습한 AI 튜터가 도와드려요!</p>
                    </div>
                    <button onclick="closeAIChat()" class="text-white hover:text-gray-200 transition-all">
                        <span class="text-2xl">✕</span>
                    </button>
                </div>
            </div>
            
            <!-- 채팅 영역 -->
            <div id="ai-chat-messages" class="flex-1 overflow-y-auto p-6 bg-gray-50" style="height: calc(100% - 180px);">
                <!-- 초기 메시지는 JavaScript에서 동적으로 생성됩니다 -->
            </div>
            
            <!-- 입력 영역 -->
            <div class="border-t p-4 bg-white rounded-b-2xl flex-shrink-0">
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        id="ai-chat-input" 
                        placeholder="질문을 입력하세요... (예: 이번 시험 범위는 뭐야?)"
                        class="flex-1 p-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:outline-none"
                        onkeypress="if(event.key==='Enter') sendAIMessage()"
                    >
                    <button 
                        onclick="sendAIMessage()"
                        id="ai-send-btn"
                        class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all flex items-center gap-2"
                    >
                        <span>전송</span>
                        <span>📤</span>
                    </button>
                </div>
                
                <!-- 예시 질문 버튼들 -->
                <div class="flex flex-wrap gap-2 mt-3">
                    <button onclick="setAIQuestion('이번 시험 범위가 뭐야?')" class="text-xs bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-gray-600">
                        시험 범위 📖
                    </button>
                    <button onclick="setAIQuestion('수학 공부 팁 좀 알려줘')" class="text-xs bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-gray-600">
                        공부 팁 💡
                    </button>
                    <button onclick="setAIQuestion('어떤 문제가 자주 나와?')" class="text-xs bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-gray-600">
                        출제 경향 📊
                    </button>
                    <button onclick="setAIQuestion('시험 전날 뭘 해야 해?')" class="text-xs bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-gray-600">
                        시험 전날 🌙
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 알림 팝업 -->
    <div id="notifications-popup" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <!-- 팝업 헤더 -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold">🔔 알림</h2>
                        <p class="text-purple-100 text-sm mt-1">새로운 시험 정보 업데이트</p>
                    </div>
                    <button onclick="closeNotifications()" class="text-white hover:text-gray-200 transition-all">
                        <span class="text-2xl">✕</span>
                    </button>
                </div>
            </div>
            
            <!-- 알림 액션 바 -->
            <div class="border-b p-4 flex justify-between items-center bg-gray-50">
                <div class="flex gap-2">
                    <span id="notification-count" class="text-sm text-gray-600">읽지 않은 알림: 0개</span>
                </div>
                <div class="flex gap-2">
                    <button onclick="markAllRead()" class="text-sm text-indigo-600 hover:text-indigo-700">
                        모두 읽음
                    </button>
                    <button onclick="clearAllNotifications()" class="text-sm text-red-600 hover:text-red-700">
                        모두 삭제
                    </button>
                </div>
            </div>
            
            <!-- 알림 목록 -->
            <div id="notifications-list" class="max-h-96 overflow-y-auto p-4">
                <div id="notifications-loading" class="text-center py-8">
                    <div class="text-gray-500">
                        <div class="text-3xl mb-2">⏳</div>
                        <p>알림을 불러오는 중...</p>
                    </div>
                </div>
                
                <div id="notifications-content" class="hidden space-y-3">
                    <!-- 알림 항목들이 여기에 동적으로 추가됩니다 -->
                </div>
                
                <div id="no-notifications" class="hidden text-center py-8 text-gray-500">
                    <div class="text-3xl mb-2">🔕</div>
                    <p>새로운 알림이 없습니다</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 시험 정보 팝업 -->
    <div id="exam-info-popup" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[80vh] overflow-hidden">
            <!-- 팝업 헤더 -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">📋 시험 정보</h2>
                    <button onclick="closeExamInfo()" class="text-white hover:text-gray-200 transition-all">
                        <span class="text-2xl">✕</span>
                    </button>
                </div>
            </div>
            
            <!-- 팝업 바디 -->
            <div class="p-6">
                <!-- 메인 버튼들 -->
                <div id="exam-info-main" class="grid grid-cols-2 gap-4">
                    <button onclick="showExamResources()" class="p-6 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all group">
                        <div class="text-4xl mb-3">📁</div>
                        <h3 class="text-lg font-semibold text-gray-800">시험 자료 보기</h3>
                        <p class="text-sm text-gray-600 mt-2">업로드된 파일 및 링크 확인</p>
                    </button>
                    
                    <button onclick="showExamTips()" class="p-6 bg-green-50 hover:bg-green-100 rounded-xl transition-all group">
                        <div class="text-4xl mb-3">💡</div>
                        <h3 class="text-lg font-semibold text-gray-800">시험 정보 보기</h3>
                        <p class="text-sm text-gray-600 mt-2">팁과 조언 확인</p>
                    </button>
                </div>
                
                <!-- 자료 목록 (초기에는 숨김) -->
                <div id="exam-resources-list" class="hidden">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <span>📁</span>
                            시험 자료
                        </h3>
                        <button onclick="backToExamInfoMain()" class="text-indigo-600 hover:text-indigo-700">
                            ← 뒤로
                        </button>
                    </div>
                    
                    <div id="resources-loading" class="text-center py-8">
                        <div class="text-gray-500">
                            <div class="text-3xl mb-2">⏳</div>
                            <p>자료를 불러오는 중...</p>
                        </div>
                    </div>
                    
                    <div id="resources-content" class="hidden max-h-96 overflow-y-auto space-y-2">
                        <!-- 자료 목록이 여기에 동적으로 추가됩니다 -->
                    </div>
                    
                    <div id="no-resources" class="hidden text-center py-8 text-gray-500">
                        <div class="text-3xl mb-2">📂</div>
                        <p>아직 업로드된 자료가 없습니다.</p>
                    </div>
                </div>
                
                <!-- 팁 목록 (초기에는 숨김) -->
                <div id="exam-tips-list" class="hidden">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <span>💡</span>
                            시험 팁 & 정보
                        </h3>
                        <button onclick="backToExamInfoMain()" class="text-indigo-600 hover:text-indigo-700">
                            ← 뒤로
                        </button>
                    </div>
                    
                    <div id="tips-loading" class="text-center py-8">
                        <div class="text-gray-500">
                            <div class="text-3xl mb-2">⏳</div>
                            <p>정보를 불러오는 중...</p>
                        </div>
                    </div>
                    
                    <div id="tips-content" class="hidden max-h-96 overflow-y-auto space-y-3">
                        <!-- 팁 목록이 여기에 동적으로 추가됩니다 -->
                    </div>
                    
                    <div id="no-tips" class="hidden text-center py-8 text-gray-500">
                        <div class="text-3xl mb-2">💭</div>
                        <p>아직 등록된 정보가 없습니다.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 업로드 모달 -->
    <div id="upload-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <!-- 모달 헤더 -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-6 rounded-t-3xl">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold flex items-center gap-3">
                        <span class="text-3xl">📤</span>
                        시험 정보 업로드
                    </h2>
                    <button onclick="closeUploadModal()" class="text-white hover:text-gray-200 text-2xl">
                        ✕
                    </button>
                </div>
            </div>
            
            <!-- 모달 바디 -->
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- 업로드 타입 선택 -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">업로드 유형 선택</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <button onclick="selectUploadType('file')" id="upload-type-file" 
                                class="p-4 border-2 border-gray-200 rounded-xl hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                            <div class="text-3xl mb-2">📁</div>
                            <p class="font-medium">파일 업로드</p>
                            <p class="text-sm text-gray-600 mt-1">이미지, PDF 등</p>
                        </button>
                        <button onclick="selectUploadType('text')" id="upload-type-text"
                                class="p-4 border-2 border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all">
                            <div class="text-3xl mb-2">💡</div>
                            <p class="font-medium">정보 입력</p>
                            <p class="text-sm text-gray-600 mt-1">팁, 조언 등</p>
                        </button>
                    </div>
                </div>
                
                <!-- 파일 업로드 섹션 -->
                <div id="file-upload-section" class="hidden">
                    <h3 class="text-lg font-semibold mb-4">파일 선택</h3>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center">
                        <input type="file" id="exam-file-input" class="hidden" accept="image/*,application/pdf" onchange="handleFileSelect(event)">
                        <label for="exam-file-input" class="cursor-pointer">
                            <div class="text-5xl mb-3">📤</div>
                            <p class="text-gray-600">클릭하여 파일 선택</p>
                            <p class="text-sm text-gray-500 mt-2">이미지, PDF 파일 (최대 10MB)</p>
                        </label>
                    </div>
                    
                    <div id="file-preview" class="mt-4 hidden">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600">선택된 파일:</p>
                            <p id="selected-file-name" class="font-medium"></p>
                        </div>
                    </div>
                    
                    <button id="upload-file-btn" onclick="uploadFile()" 
                            class="w-full mt-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl py-3 font-medium hover:from-indigo-700 hover:to-purple-700 transition-all disabled:opacity-50" 
                            disabled>
                        📁 파일 업로드하기
                    </button>
                </div>
                
                <!-- 정보 입력 섹션 -->
                <div id="text-upload-section" class="hidden">
                    <h3 class="text-lg font-semibold mb-4">정보 입력</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">정보 유형</label>
                        <select id="tip-type" class="w-full p-3 border border-gray-300 rounded-lg">
                            <option value="">유형 선택</option>
                            <option value="tip">시험 팁</option>
                            <option value="range">출제 범위</option>
                            <option value="schedule">시험 일정</option>
                            <option value="other">기타</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">내용 입력</label>
                        <textarea id="tip-content" rows="5" 
                                  class="w-full p-3 border border-gray-300 rounded-lg resize-none"
                                  placeholder="시험에 도움이 될 정보를 입력하세요..."></textarea>
                    </div>
                    
                    <button id="upload-tip-btn" onclick="uploadTip()" 
                            class="w-full bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-xl py-3 font-medium hover:from-green-700 hover:to-teal-700 transition-all">
                        💡 정보 업로드하기
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 헤더 정보 저장 함수
        function saveHeaderInfo() {
            if (userData.school) {
                document.getElementById('header-school').textContent = userData.school;
            }
            if (userData.grade) {
                document.getElementById('header-grade').textContent = userData.grade + '학년';
            }
            if (userData.examType) {
                const examType = examTypes.find(e => e.id === userData.examType);
                document.getElementById('header-exam').textContent = examType ? examType.name : '-';
            }
        }
        
        
        // 날짜/시간 업데이트 함수
        function updateDateTime() {
            const now = new Date();
            const dateStr = now.toLocaleDateString('ko-KR', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                weekday: 'short'
            });
            const timeStr = now.toLocaleTimeString('ko-KR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            const datetimeElement = document.getElementById('current-datetime');
            if (datetimeElement) {
                datetimeElement.textContent = `${dateStr} ${timeStr}`;
            }
        }
        
        // 1초마다 시간 업데이트
        setInterval(updateDateTime, 1000);
        
        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            // 즉시 날짜/시간 표시
            updateDateTime();
            
            // 학교 입력 이벤트 리스너 추가
            const schoolInput = document.getElementById('school-input');
            if (schoolInput) {
                schoolInput.addEventListener('input', checkSection0Complete);
            }
            
            // PHP에서 전달받은 데이터가 있으면 폼에 채우기
            if (userData.school) {
                document.getElementById('school-input').value = userData.school;
            }
            if (userData.grade) {
                selectGrade(userData.grade);
            }
            if (userData.examType) {
                selectExamType(userData.examType);
            }
            
            // 기존 데이터가 있으면 대시보드로 이동
            if (userData.school && userData.grade && userData.examType) {
                // 헤더 정보 업데이트
                saveHeaderInfo();
                
                // 모든 섹션을 완료로 표시
                completedSections = [0, 1, 2, 3];
                currentSection = 4;
                updateProgress();
                updateNavigation();
                
                // 대시보드로 이동
                setTimeout(() => {
                    goToSection(4);
                    // 기본은 스크롤 모드로 설정
                    setDashboardMode('scroll');
                }, 100);
            } else {
                // 새로운 사용자인 경우 타이핑 효과 시작
                startTypingEffect();
            }
            
            // 대시보드 직접 접근 처리
            if (isDashboardDirect) {
                setTimeout(() => {
                    goToSection(4);
                    setDashboardMode('scroll');
                }, 200);
            }
            
            // 모드 전환 버튼 이벤트 리스너 추가
            const scrollModeBtn = document.getElementById('scroll-mode-btn');
            const tabModeBtn = document.getElementById('tab-mode-btn');
            
            if (scrollModeBtn) {
                scrollModeBtn.addEventListener('click', function() {
                    console.log('스크롤 모드 버튼 클릭');
                    setDashboardMode('scroll');
                });
            }
            
            if (tabModeBtn) {
                tabModeBtn.addEventListener('click', function() {
                    console.log('탭 모드 버튼 클릭');
                    setDashboardMode('tab');
                });
            }
        });
    </script>
</body>
</html>