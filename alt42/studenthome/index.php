<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// GET 파라미터에서 userid 가져오기, 없으면 현재 로그인한 사용자 ID 사용
$studentid = isset($_GET["userid"]) ? intval($_GET["userid"]) : $USER->id;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';

// 대상 학생 정보 가져오기
$student_info = $DB->get_record_sql("SELECT id, firstname, lastname, email FROM mdl_user WHERE id='$studentid'");
if (!$student_info) {
    // 사용자가 존재하지 않는 경우 현재 사용자로 설정
    $studentid = $USER->id;
    $student_info = $DB->get_record_sql("SELECT id, firstname, lastname, email FROM mdl_user WHERE id='$studentid'");
}

$student_name = $student_info->firstname . ' ' . $student_info->lastname;

// 권한 확인: 본인이거나 교사/관리자인 경우만 접근 허용
if ($studentid != $USER->id && $role === 'student') {
    // 학생이 다른 학생의 정보에 접근하려는 경우 거부
    echo '<div style="text-align:center;padding:50px;"><h2>⚠️ 접근 권한이 없습니다</h2><p>본인의 학습 정보만 확인할 수 있습니다.</p><a href="index.php">내 학습 홈으로 이동</a></div>';
    exit;
}

// 각 페이지별 마지막 선택 정보 가져오기 
$last_selections = array();
$page_types = array('index1', 'index2', 'index3', 'index4');
$most_recent_page = null;
$most_recent_time = 0;

foreach ($page_types as $page_type) {
    $selection = $DB->get_record('user_learning_selections',  
        array('userid' => $studentid, 'page_type' => $page_type)
    );
    if ($selection) {
        // timecreated와 timemodified 중 더 최근 시간 사용
        $timecreated = is_numeric($selection->timecreated) ? (int)$selection->timecreated : strtotime($selection->timecreated);
        $timemodified = isset($selection->timemodified) && $selection->timemodified ? 
            (is_numeric($selection->timemodified) ? (int)$selection->timemodified : strtotime($selection->timemodified)) : 
            $timecreated;
        
        // 더 최근 시간 선택
        $timestamp = max($timecreated, $timemodified);
        
        // 디버깅: 각 페이지의 timestamp 확인
        error_log("Page: {$page_type}, created: {$timecreated}, modified: {$timemodified}, final: {$timestamp}, date: " . date('Y-m-d H:i:s', $timestamp));
        
        $last_selections[$page_type] = array(
            'last_path' => $selection->last_path,
            'last_unit' => $selection->last_unit,
            'last_topic' => $selection->last_topic,
            'selection_data' => json_decode($selection->selection_data, true),
            'timecreated' => $timestamp
        );
        
        // 가장 최근 페이지 찾기
        if ($timestamp > $most_recent_time) {
            $most_recent_time = $timestamp;
            $most_recent_page = $page_type;
        }
    }
}

// 시간 차이 계산 함수
function getTimeAgo($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    
    // 디버깅
    error_log("getTimeAgo: timestamp={$timestamp}, now={$now}, diff={$diff}");
    
    if ($diff < 86400) { // 24시간 미만
        return "오늘";
    } elseif ($diff < 172800) { // 48시간 미만
        return "어제";
    } elseif ($diff < 604800) { // 7일 미만
        $days = floor($diff / 86400);
        return $days . "일 전";
    } elseif ($diff < 2419200) { // 4주 (28일) 미만
        $weeks = floor($diff / 604800);
        return $weeks . "주 전";
    } elseif ($diff < 31536000) { // 1년 미만
        $months = floor($diff / 2592000); // 30일로 계산
        return $months . "개월 전";
    } else {
        return "오래 전";
    }
}

// 현재 사용자의 학습 모드 가져오기
$current_mode = null;
$mode_display = array(
    'curriculum' => array('title' => '커리큘럼 중심', 'icon' => '📚'),
    'custom' => array('title' => '맞춤학습 중심', 'icon' => '🎯'),
    'exam' => array('title' => '시험대비 중심', 'icon' => '✏️'),
    'mission' => array('title' => '단기미션 중심', 'icon' => '⚡'),
    'reflection' => array('title' => '자기성찰 중심', 'icon' => '🧠'),
    'selfled' => array('title' => '자기주도 중심', 'icon' => '🚀'),
    'cognitive' => array('title' => '도제학습 중심', 'icon' => '🔍'),
    'timecentered' => array('title' => '시간성찰 중심', 'icon' => '🕒'),
    'curiositycentered' => array('title' => '탐구학습 중심', 'icon' => '🔭')
);

try {
    // 학생 본인의 모드 조회 (학생이 직접 선택한 경우 또는 선생님이 설정한 경우)
    $persona_mode = $DB->get_record_sql(
        "SELECT * FROM {persona_modes} WHERE student_id = :studentid ORDER BY timecreated DESC LIMIT 1",
        array('studentid' => $studentid)
    );
    
    if ($persona_mode && !empty($persona_mode->student_mode)) {
        $current_mode = $persona_mode->student_mode;
    }
} catch (Exception $e) {
    error_log("Error getting persona mode: " . $e->getMessage());
}

// 디버깅: 데이터 확인
error_log("Last selections for user $studentid: " . json_encode($last_selections));
error_log("Most recent page: $most_recent_page with time: $most_recent_time");

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM 홈</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            color: #2c3e50;
            line-height: 1.6;
            display: flex;
            margin: 0;
        }

        .main-content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .view-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .view-toggle-btn {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid transparent;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .view-toggle-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        /* 메인 컨테이너 */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        /* 최근 학습 섹션 */
        .recent-learning {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 3rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }

        .recent-learning h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }

        .recent-items {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .recent-item {
            background: #f8f9fa;
            border: none;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
            position: relative;
        }

        .recent-item:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .recent-item.empty {
            opacity: 0.5;
            cursor: default;
        }

        .recent-item.empty:hover {
            transform: none;
            box-shadow: none;
        }

        .recent-item-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .recent-item-icon {
            font-size: 1.5rem;
        }

        .recent-item-type {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6c757d;
        }

        .recent-item-content {
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 2rem;
        }

        .recent-item-unit {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .recent-item-topic {
            color: #6c757d;
            font-size: 0.8rem;
        }
        
        .recent-item-time {
            position: absolute;
            bottom: 0.75rem;
            right: 1rem;
            font-size: 0.75rem;
            color: #666;
            font-weight: 500;
        }
        
        .recent-item.most-recent {
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
            background: rgba(59, 130, 246, 0.05);
        }

        .no-recent-message {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .no-recent-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .no-recent-message p {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .no-recent-message small {
            font-size: 0.9rem;
            color: #868e96;
        }


        /* 메인 메뉴 카드들 */
        .main-menu {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .menu-card {
            background: white;
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
            border-color: transparent;
        }
        
        .menu-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--card-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }
        
        .menu-card:hover::after {
            opacity: 0.05;
        }

        .menu-card.concept {
            --card-gradient: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
        
        .menu-card.concept .icon {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .menu-card.advanced {
            --card-gradient: linear-gradient(135deg, #8b5cf6, #6d28d9);
        }
        
        .menu-card.advanced .icon {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .menu-card.exam {
            --card-gradient: linear-gradient(135deg, #10b981, #059669);
        }
        
        .menu-card.exam .icon {
            background: linear-gradient(135deg, #10b981, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .menu-card.suneung {
            --card-gradient: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .menu-card.suneung .icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .menu-card > * {
            position: relative;
            z-index: 1;
        }

        .menu-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .menu-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .menu-card p {
            color: #6c757d;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        /* 학년별 선택 섹션 */
        .grade-section {
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        
        .grade-section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 2rem;
            text-align: center;
        }

        .grade-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }

        .grade-tab {
            padding: 0.75rem 2rem;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            color: #495057;
            font-size: 0.95rem;
        }

        .grade-tab:hover {
            background: #e9ecef;
            color: #2c3e50;
        }

        .grade-tab.active {
            background: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }

        /* 타일 그리드 */
        .tiles-container {
            display: none;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tiles-container.active {
            display: block;
            opacity: 1;
        }

        .tiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .tile {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            color: #495057;
            position: relative;
            font-size: 0.95rem;
        }

        .tile:hover {
            background: white;
            border-color: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .tile.selected {
            background: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }

        .tile.selected::after {
            content: '✓';
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            font-size: 1rem;
            font-weight: bold;
        }


        /* 선택 완료 버튼 */
        .action-buttons {
            text-align: center;
            margin-top: 2.5rem;
        }

        .start-button {
            padding: 0.875rem 2.5rem;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .start-button:hover {
            background: #34495e;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
        }

        .start-button:active {
            transform: translateY(0);
        }

        .start-button:disabled {
            background: #ced4da;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* 선택된 항목 표시 */
        .selected-items {
            margin-top: 1.5rem;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #6c757d;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        /* 애니메이션 */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .menu-card {
            animation: fadeIn 0.4s ease-out;
            animation-fill-mode: both;
        }

        .menu-card:nth-child(1) { animation-delay: 0.05s; }
        .menu-card:nth-child(2) { animation-delay: 0.1s; }
        .menu-card:nth-child(3) { animation-delay: 0.15s; }
        .menu-card:nth-child(4) { animation-delay: 0.2s; }

        /* 포커스 스타일 */
        button:focus,
        .tile:focus,
        .menu-card:focus {
            outline: 2px solid #2c3e50;
            outline-offset: 2px;
        }

        /* 사용자 정보 표시 */
        .user-info-container {
            margin: 1rem 0;
            text-align: center;
        }

        .viewing-user-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
        }

        .user-indicator {
            color: white;
            font-size: 0.9rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .back-to-my-account {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 1.5rem;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .back-to-my-account:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }

        .current-user-info {
            padding: 0.5rem 0;
        }

        .welcome-message {
            color: white;
            font-size: 1rem;
            font-weight: 500;
        }

        /* 선생님 모드 전환 버튼 */
        .teacher-mode-container {
            position: absolute;
            top: 50%;
            left: 2rem;
            transform: translateY(-50%);
        }

        .teacher-mode-button {
            padding: 0.5rem 1rem;
            background: #059669;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 1rem;
            text-decoration: none;
        }

        .teacher-mode-button:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }

        /* 메타인지 바로가기 버튼 */
        .meta-shortcut-container {
            position: absolute;
            top: 50%;
            right: 2rem;
            transform: translateY(-50%);
        }

        .meta-shortcut-button {
            padding: 0.5rem 1rem;
            background: #8b5cf6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 1rem;
            text-decoration: none;
        }

        .meta-shortcut-button:hover {
            background: #7c3aed;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        
        .content-wrapper {
            padding: 30px 20px 0;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Navigation Responsive Styles */
        @media (max-width: 768px) {
            .nav-controls {
                flex-direction: column;
                gap: 15px;
            }

            .header-nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }

            .nav-btn {
                padding: 10px 16px;
                font-size: 13px;
                min-width: auto;
            }

            .content-container {
                padding: 0 15px;
            }

            .nav-top {
                padding: 15px 0;
            }
        }

        @media (max-width: 480px) {
            .header-nav {
                gap: 6px;
            }

            .nav-btn {
                padding: 8px 12px;
                font-size: 12px;
                border-radius: 25px;
            }

            .nav-top {
                padding: 12px 0;
            }

            .content-container {
                padding: 0 10px;
            }
        }

        /* 반응형 */
        @media (max-width: 1024px) {
            .main-menu {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .main-menu {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .recent-items {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .menu-card {
                padding: 2rem 1.5rem;
            }
            
            .grade-tabs {
                gap: 0.75rem;
            }
            
            .grade-tab {
                padding: 0.625rem 1.25rem;
                font-size: 0.875rem;
            }
            
            .grade-section {
                padding: 2rem 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .main-menu {
                grid-template-columns: 1fr;
            }
            
            .recent-items {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 1.75rem;
            }
            
            .header p {
                font-size: 0.875rem;
            }
            
            .menu-card h2 {
                font-size: 1.125rem;
            }
            
            .tiles-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
            
            .meta-shortcut-container {
                right: 1rem;
            }
            
            .meta-shortcut-button {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
            
            .teacher-mode-container {
                left: 1rem;
            }
            
            .teacher-mode-button {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <?php
    // 에이전트 휠 메뉴 포함
    include_once('includes/agent_wheel.php');
    ?>

    <div class="main-content-wrapper">
        <?php
        // 헤더 컴포넌트 포함
        $active_page = 'index';
        include 'includes/header.php';
        ?>

    <div class="content-wrapper">
        <!-- 메인 컨테이너 -->
        <div class="main-container">
        
        <!-- 최근 학습 섹션 -->
        <?php if (!empty($last_selections)): ?>
        <div class="recent-learning">
            <h2>🔄 <?php echo htmlspecialchars($student_name); ?>님의 최근 학습 이어하기</h2>
            <div class="recent-items">
                <?php
                $page_info = array(
                    'index1' => array('name' => '개념학습', 'icon' => '📚'),
                    'index2' => array('name' => '심화학습', 'icon' => '🚀'),
                    'index3' => array('name' => '내신준비', 'icon' => '📝'),
                    'index4' => array('name' => '수능대비', 'icon' => '🎯')
                );
                
                foreach ($page_info as $page => $info):
                    if (isset($last_selections[$page])):
                        $selection = $last_selections[$page];
                        $timeAgo = getTimeAgo($selection['timecreated']);
                        $isMostRecent = ($page === $most_recent_page);
                        
                        // 각 페이지별로 직접 mathking.kr 링크로 이동
                        $href = "{$page}.php?userid={$studentid}&direct=true"; // 기본값
                        
                        if (isset($selection['selection_data'])) {
                            $grade = $selection['selection_data']['grade'] ?? '';
                            $last_topic = $selection['last_topic'] ?? '';
                            
                            if ($page === 'index1') {
                                // 개념학습 - books/chapter.php 링크
                                $cidMap = array(
                                    // 초등수학
                                    '4-1' => 73, '4-2' => 74, '5-1' => 75, '5-2' => 76, '6-1' => 78, '6-2' => 79,
                                    // 중등수학
                                    '1-1' => 66, '1-2' => 67, '2-1' => 68, '2-2' => 69, '3-1' => 71, '3-2' => 72,
                                    // 고등수학
                                    'common1' => 106, 'common2' => 107, 'algebra' => 61, 
                                    'calculus1' => 62, 'stats' => 64, 'calculus2' => 63, 'geometry' => 65
                                );
                                
                                $cid = isset($cidMap[$last_topic]) ? $cidMap[$last_topic] : null;
                                if ($cid) {
                                    $href = "https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?&cid={$cid}&type=init&userid={$studentid}";
                                }
                            } elseif ($page === 'index2') {
                                // 심화학습 링크 처리
                                if ($grade === 'elementary') {
                                    // 초등수학 - checklist 링크
                                    $checklistMap = array(
                                        '4-1' => 40054, '4-2' => 40055, '5-1' => 40056,
                                        '5-2' => 40057, '6-1' => 40058, '6-2' => 40059
                                    );
                                    if (isset($checklistMap[$last_topic])) {
                                        $href = "https://mathking.kr/moodle/mod/checklist/view.php?id={$checklistMap[$last_topic]}&userid={$studentid}";
                                    }
                                } elseif ($grade === 'middle') {
                                    // 중등수학 - missionhome 또는 특별 링크
                                    if ($last_topic === 'kmc') {
                                        $href = "https://mathking.kr/moodle/mod/checklist/index.php?id=142&userid={$studentid}";
                                    } elseif ($last_topic === 'kmo') {
                                        $href = "https://mathking.kr/moodle/mod/checklist/view.php?id=4186&userid={$studentid}";
                                    } elseif ($last_topic === 'special') {
                                        $href = "https://mathking.kr/moodle/mod/checklist/index.php?id=275&userid={$studentid}";
                                    } else {
                                        $cidMap = array(
                                            '1-1' => 24, '1-2' => 25, '2-1' => 26,
                                            '2-2' => 27, '3-1' => 28, '3-2' => 29
                                        );
                                        if (isset($cidMap[$last_topic])) {
                                            $href = "https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=2&cid={$cidMap[$last_topic]}&tb=90&userid={$studentid}";
                                        }
                                    }
                                } elseif ($grade === 'high') {
                                    // 고등수학 - missionhome 링크
                                    $cidMap = array(
                                        'common1' => 1, 'common2' => 30, 'algebra' => 31,
                                        'calculus1' => 32, 'statistics' => 35,
                                        'calculus2' => 33, 'geometry' => 34
                                    );
                                    if (isset($cidMap[$last_topic])) {
                                        $href = "https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=2&cid={$cidMap[$last_topic]}&tb=90&userid={$studentid}";
                                    }
                                }
                            } elseif ($page === 'index3') {
                                // 내신준비 링크 처리
                                if ($grade === 'elementary') {
                                    // 초등수학 - books/chapter.php 링크
                                    $cidMap = array(
                                        '4-1' => 73, '4-2' => 74, '5-1' => 75,
                                        '5-2' => 76, '6-1' => 78, '6-2' => 79
                                    );
                                    if (isset($cidMap[$last_topic])) {
                                        $href = "https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?&cid={$cidMap[$last_topic]}&type=init&userid={$studentid}";
                                    }
                                } elseif ($grade === 'middle') {
                                    // 중등수학 - missionhome 링크 (mtid=3)
                                    $cidMap = array(
                                        '1-1' => 42, '1-2' => 43, '2-1' => 44,
                                        '2-2' => 45, '3-1' => 46, '3-2' => 47
                                    );
                                    if (isset($cidMap[$last_topic])) {
                                        $href = "https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=3&cid={$cidMap[$last_topic]}&tb=90&userid={$studentid}";
                                    }
                                } elseif ($grade === 'high') {
                                    // 고등수학 - missionhome 링크 (mtid=3)
                                    $cidMap = array(
                                        'common1' => 2, 'common2' => 36, 'algebra' => 37,
                                        'calculus1' => 38, 'statistics' => 40,
                                        'calculus2' => 39, 'geometry' => 41
                                    );
                                    if (isset($cidMap[$last_topic])) {
                                        $href = "https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=3&cid={$cidMap[$last_topic]}&tb=90&userid={$studentid}";
                                    }
                                }
                            } elseif ($page === 'index4') {
                                // 수능대비 링크 처리 - 추후 데이터 확인 후 구현
                                // 현재는 기본 링크 사용
                                $href = "{$page}.php?userid={$studentid}&direct=true";
                            }
                        }
                ?>
                    <a href="<?php echo $href; ?>" class="recent-item <?php echo $isMostRecent ? 'most-recent' : ''; ?>">
                        <div class="recent-item-header">
                            <span class="recent-item-icon"><?php echo $info['icon']; ?></span>
                            <span class="recent-item-type"><?php echo $info['name']; ?></span>
                        </div>
                        <div class="recent-item-content">
                            <div class="recent-item-unit"><?php echo htmlspecialchars($selection['last_unit']); ?></div>
                            <div class="recent-item-topic"><?php echo htmlspecialchars($selection['last_topic']); ?></div>
                        </div>
                        <span class="recent-item-time"><?php echo $timeAgo; ?></span>
                    </a>
                <?php else: ?>
                    <div class="recent-item empty">
                        <div class="recent-item-header">
                            <span class="recent-item-icon"><?php echo $info['icon']; ?></span>
                            <span class="recent-item-type"><?php echo $info['name']; ?></span>
                        </div>
                        <div class="recent-item-content">
                            <div class="recent-item-unit">아직 학습 기록 없음</div>
                        </div>
                        <span class="recent-item-time">미방문</span>
                    </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- 학습 기록이 없는 경우 안내 메시지 -->
        <div class="recent-learning">
            <h2>👋 <?php echo htmlspecialchars($student_name); ?>님, 환영합니다!</h2>
            <div class="no-recent-message">
                <div class="no-recent-icon">🎯</div>
                <p>아직 학습 기록이 없습니다. 아래 메뉴에서 학습을 시작해보세요!</p>
                <small>학습을 시작하면 여기에 최근 학습 이어가기가 표시됩니다.</small>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 메인 메뉴 카드들 -->
        <div class="main-menu">
            <div class="menu-card concept" onclick="navigateTo('index1.php?userid=<?php echo $studentid; ?>')">
                <div class="icon">📚</div>
                <h2>개념학습</h2>
                <p>기초부터 차근차근</p>
            </div>
            
            <div class="menu-card advanced" onclick="navigateTo('index2.php?userid=<?php echo $studentid; ?>')">
                <div class="icon">🚀</div>
                <h2>심화학습</h2>
                <p>실력 향상을 위한 도전</p>
            </div>
            
            <div class="menu-card exam" onclick="navigateTo('index3.php?userid=<?php echo $studentid; ?>')">
                <div class="icon">📝</div>
                <h2>내신준비</h2>
                <p>학교 시험 완벽 대비</p>
            </div>
            
            <div class="menu-card suneung" onclick="navigateTo('index4.php?userid=<?php echo $studentid; ?>')">
                <div class="icon">🎯</div>
                <h2>수능대비</h2>
                <p>수능 만점을 향해</p>
            </div>
        </div>

    </div>

    </div>
    
    <!-- Floating Chatbot Button -->
    <div id="chatbotButton" class="chatbot-button">
        <span class="chatbot-icon">📚</span>
        <span class="chatbot-label">학습 도우미</span>
    </div>
    
    <!-- Chatbot Panel -->
    <div id="chatbotPanel" class="chatbot-panel">
        <div class="chatbot-header">
            <div class="chatbot-title">
                <span class="chatbot-avatar">🤖</span>
                <div class="chatbot-info">
                    <h3>학습 도우미</h3>
                    <div class="chatbot-mode-inline">
                        <span class="mode-icon-small"><?php echo $mode_display[$current_mode]['icon'] ?? '📚'; ?></span>
                        <span class="mode-text-small"><?php echo $mode_display[$current_mode]['title'] ?? '체계적 진도형'; ?></span>
                        <a href="selectmode.php?userid=<?php echo $studentid; ?>" class="mode-change-link">변경</a>
                    </div>
                </div>
            </div>
            <button class="chatbot-close" onclick="toggleChatbot()">✕</button>
        </div>
        
        <div class="chatbot-messages" id="chatMessages">
            <div class="chat-message bot">
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <p>안녕하세요! <?php echo htmlspecialchars($student_name); ?>님 👋</p>
                    <p>저는 <?php echo $mode_display[$current_mode]['icon'] ?? '📚'; ?> <?php echo $mode_display[$current_mode]['title'] ?? '체계적 진도형'; ?> 학습 도우미입니다.</p>
                    <?php 
                    // Show mode-specific welcome message
                    $welcome_messages = [
                        'curriculum' => '<p>📚 "진도는 전략, 보정은 일상!" 오늘의 학습 목표와 주간 진도를 함께 점검해봐요.</p>',
                        'exam' => '<p>✏️ "시험은 전투, 출제자는 상대!" D-day까지 전략적으로 준비해요. 오늘 목표는 50문항!</p>',
                        'custom' => '<p>🎯 "모든 학생은 고유한 학습 DNA를 가진다!" 당신만의 학습 스타일을 찾아드릴게요.</p>',
                        'mission' => '<p>⚡ "작은 승리가 큰 성공을 만든다!" 오늘의 5개 미션, 함께 도전해볼까요?</p>',
                        'reflection' => '<p>🧠 "이해 없는 정답은 무의미하다!" 왜?를 통해 진짜 실력을 키워요.</p>',
                        'selfled' => '<p>🚀 "스스로 설계한 길이 가장 빠른 길!" 자율적인 학습 계획을 응원합니다.</p>',
                        'cognitive' => '<p>🔍 "마스터의 사고를 모방하며 성장한다!" 전문가의 사고 과정을 함께 따라가봐요.</p>',
                        'timecentered' => '<p>🕒 "시간은 학습의 생명선!" 25분 집중, 5분 휴식으로 효율을 극대화해요.</p>',
                        'curiositycentered' => '<p>💡 "궁금증이 최고의 선생님!" 오늘은 어떤 질문으로 시작해볼까요?</p>'
                    ];
                    echo $welcome_messages[$current_mode] ?? '<p>무엇을 도와드릴까요?</p>';
                    ?>
                </div>
            </div>
        </div>
        
        <div class="chatbot-input">
            <input type="text" id="chatInput" placeholder="메시지를 입력하세요..." onkeypress="handleChatKeyPress(event)">
            <button onclick="sendChatMessage()" class="chat-send-btn">
                <span>전송</span>
            </button>
        </div>
    </div>
    
    <style>
        /* Floating Chatbot Button */
        .chatbot-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease, opacity 0.3s ease, transform 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }
        
        .chatbot-button.hidden {
            opacity: 0;
            pointer-events: none;
            transform: scale(0.8);
        }
        
        .chatbot-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 30px rgba(102, 126, 234, 0.6);
            width: auto;
            padding: 0 20px;
            border-radius: 30px;
        }
        
        .chatbot-icon {
            font-size: 28px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .chatbot-label {
            display: none;
            color: white;
            margin-left: 10px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .chatbot-button:hover .chatbot-label {
            display: inline;
        }
        
        /* Chatbot Panel */
        .chatbot-panel {
            position: fixed;
            top: 88px; /* Start exactly at header bottom (header height is 88px) */
            right: -35%;
            width: 33.33%;
            height: calc(100vh - 88px); /* Adjust height to account for header */
            background: white;
            box-shadow: -4px 0 20px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1001; /* Higher than header z-index (1000) */
            display: flex;
            flex-direction: column;
        }
        
        .chatbot-panel.active {
            right: 0;
        }
        
        .chatbot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-top-left-radius: 0;
        }
        
        /* Inline mode display in header */
        .chatbot-mode-inline {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            background: rgba(255,255,255,0.15);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .mode-icon-small {
            font-size: 14px;
        }
        
        .mode-text-small {
            font-size: 11px;
            font-weight: 500;
            opacity: 0.95;
        }
        
        .mode-change-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 10px;
            margin-left: 4px;
            padding: 2px 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .mode-change-link:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        .chatbot-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chatbot-avatar {
            font-size: 32px;
            background: rgba(255,255,255,0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chatbot-info h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .chatbot-mode-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .chatbot-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
        }
        
        .chatbot-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        
        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .chat-message {
            display: flex;
            margin-bottom: 20px;
            animation: fadeInUp 0.3s ease;
        }
        
        .chat-message.user {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .chat-message.user .message-avatar {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .message-content {
            max-width: 70%;
            margin: 0 12px;
            padding: 12px 16px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .chat-message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .message-content p {
            margin: 0 0 8px 0;
        }
        
        .message-content p:last-child {
            margin: 0;
        }
        
        .chatbot-input {
            padding: 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
        }
        
        #chatInput {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        #chatInput:focus {
            border-color: #667eea;
        }
        
        .chat-send-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        
        .chat-send-btn:hover {
            transform: scale(1.05);
        }
        
        .chat-send-btn:active {
            transform: scale(0.95);
        }
        
        /* Loading dots animation */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 12px 16px;
        }
        
        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #999;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .chatbot-panel {
                width: 100%;
                right: -100%;
                top: 88px;
                height: calc(100vh - 88px);
            }
            
            .chatbot-button {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
            }
            
            .chatbot-button .chatbot-icon {
                font-size: 24px;
            }
            
            .chatbot-mode-bar {
                padding: 10px 15px;
            }
            
            .chatbot-mode-indicator {
                flex: 1;
            }
            
            .chatbot-mode-indicator .mode-text {
                font-size: 12px;
            }
        }
    </style>
    
    <script>
        // Current learning mode from PHP
        const currentLearningMode = '<?php echo $current_mode ?? "curriculum"; ?>';
        const studentId = <?php echo $studentid; ?>;
        const studentName = '<?php echo addslashes($student_name); ?>';
        
        // Toggle chatbot panel
        function toggleChatbot() {
            const panel = document.getElementById('chatbotPanel');
            const button = document.getElementById('chatbotButton');
            panel.classList.toggle('active');
            
            // Hide/show chatbot button
            if (panel.classList.contains('active')) {
                button.classList.add('hidden');
                // Focus input when opened
                setTimeout(() => {
                    document.getElementById('chatInput').focus();
                }, 300);
            } else {
                button.classList.remove('hidden');
            }
        }
        
        // Handle Enter key in chat input
        function handleChatKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendChatMessage();
            }
        }
        
        // Send chat message
        async function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message to chat
            addMessageToChat('user', message);
            
            // Clear input
            input.value = '';
            
            // Show typing indicator
            showTypingIndicator();
            
            try {
                // Use absolute URL to ensure correct path
                const apiUrl = window.location.origin + '/moodle/local/augmented_teacher/alt42/studenthome/chatbot_api.php';
                console.log('Calling API:', apiUrl);
                
                // Send message to server
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'send_message',
                        student_id: studentId,
                        learning_mode: currentLearningMode,
                        message: message
                    })
                });
                
                console.log('Response status:', response.status);
                
                // Check if response is ok
                if (!response.ok) {
                    if (response.status === 404) {
                        console.error('API file not found at:', apiUrl);
                        // Try to check if tables exist
                        throw new Error('API_NOT_FOUND');
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Check content type
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text.substring(0, 200));
                    throw new Error("Response is not JSON");
                }
                
                const data = await response.json();
                
                // Remove typing indicator
                removeTypingIndicator();
                
                if (data.success) {
                    // Add bot response to chat
                    addMessageToChat('bot', data.response);
                } else {
                    console.error('API Error:', data.message);
                    addMessageToChat('bot', data.message || '죄송합니다. 응답을 생성하는 중 오류가 발생했습니다.');
                }
            } catch (error) {
                console.error('Chat error details:', error);
                removeTypingIndicator();
                
                let errorMessage = '죄송합니다. 오류가 발생했습니다.\n';
                
                if (error.message === 'API_NOT_FOUND' || error.message.includes('404')) {
                    // Try fallback to simple API
                    console.log('Main API not found, trying simple fallback API...');
                    
                    try {
                        const fallbackUrl = window.location.origin + '/moodle/local/augmented_teacher/alt42/studenthome/chatbot_api_simple.php';
                        const fallbackResponse = await fetch(fallbackUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'send_message',
                                student_id: studentId,
                                learning_mode: currentLearningMode,
                                message: message
                            })
                        });
                        
                        if (fallbackResponse.ok) {
                            const fallbackData = await fallbackResponse.json();
                            if (fallbackData.success) {
                                addMessageToChat('bot', fallbackData.response + '\n\n(💡 기본 모드로 작동 중)');
                                return;
                            }
                        }
                    } catch (fallbackError) {
                        console.error('Fallback API also failed:', fallbackError);
                    }
                    
                    // If both APIs fail, show setup message
                    errorMessage = '⚠️ 챗봇 시스템이 설정되지 않았습니다.\n\n';
                    errorMessage += '다음 단계를 확인해주세요:\n';
                    errorMessage += '1. 관리자 권한으로 로그인\n';
                    errorMessage += '2. execute_chatbot_sql.php 실행하여 데이터베이스 테이블 생성\n';
                    errorMessage += '3. chatbot_api.php 파일이 올바른 위치에 있는지 확인\n\n';
                    errorMessage += '관리자에게 문의하여 설정을 완료해주세요.';
                    
                    // Add setup link for admin
                    const setupMessage = document.createElement('div');
                    setupMessage.className = 'chat-message bot';
                    setupMessage.innerHTML = `
                        <div class="message-avatar">⚙️</div>
                        <div class="message-content" style="background: #fef3c7; color: #92400e;">
                            <p>${errorMessage}</p>
                            <a href="execute_chatbot_sql.php" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                → 데이터베이스 설정 페이지로 이동
                            </a>
                        </div>
                    `;
                    document.getElementById('chatMessages').appendChild(setupMessage);
                    document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
                    return;
                } else if (error.message.includes('500')) {
                    errorMessage = '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
                } else if (error.message.includes('not JSON')) {
                    errorMessage = '서버 응답 형식이 올바르지 않습니다. PHP 오류가 있을 수 있습니다.';
                } else if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                    errorMessage = '네트워크 연결을 확인해주세요.';
                } else {
                    errorMessage += error.message;
                }
                
                addMessageToChat('bot', errorMessage);
            }
        }
        
        // Add message to chat
        function addMessageToChat(type, message) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${type}`;
            
            const avatar = type === 'user' ? '👤' : '🤖';
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <p>${escapeHtml(message)}</p>
                </div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Show typing indicator
        function showTypingIndicator() {
            const messagesContainer = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'chat-message bot';
            typingDiv.id = 'typingIndicator';
            
            typingDiv.innerHTML = `
                <div class="message-avatar">🤖</div>
                <div class="message-content">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            `;
            
            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Remove typing indicator
        function removeTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.remove();
            }
        }
        
        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
        
        // Chatbot button click handler
        document.getElementById('chatbotButton').addEventListener('click', toggleChatbot);
        
        // 페이지 이동
        function navigateTo(page) {
            window.location.href = page;
        }
        
        // 페이지 로드 시 애니메이션
        window.addEventListener('load', function() {
            document.querySelectorAll('.menu-card').forEach((card, index) => {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.opacity = '1';
                }, index * 100);
            });
        });
    </script>
    </div> <!-- main-content-wrapper 닫기 -->
</body>
</html>