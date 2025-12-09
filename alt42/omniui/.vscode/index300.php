<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login(); 

$studentid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';

// 마지막 선택 정보 가져오기
$page_type = basename($_SERVER['PHP_SELF'], '.php'); // 'index1', 'index2', etc.
$last_selection = $DB->get_record('user_learning_selections', 
    array('userid' => $studentid, 'page_type' => $page_type)
);

$should_restore = (isset($_GET['last']) && $_GET['last'] === 'true' || isset($_GET['direct']) && $_GET['direct'] === 'true') && $last_selection;
$direct_to_study = isset($_GET['direct']) && $_GET['direct'] === 'true';
?>

<!DOCTYPE html>
<html lang="ko">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>내신준비 - Math Learning Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
            min-height: 100vh;
            overflow-x: hidden;
            color: #333;
        }

        /* 네비게이션 바 */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* 미니맵 스타일 */
        .minimap-button {
            padding: 0.5rem 1rem;
            background: #30cfd0;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 1rem;
        }
        
        .minimap-button:hover {
            background: #2ab5b6;
            transform: translateY(-2px);
        }
        
        .minimap-dropdown {
            position: absolute;
            top: 60px;
            right: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 1.5rem;
            display: none;
            min-width: 250px;
            z-index: 200;
        }
        
        .minimap-dropdown.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        .minimap-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .minimap-item {
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #333;
        }
        
        .minimap-item:hover {
            background: #f0f4ff;
            transform: translateX(5px);
        }
        
        .minimap-item.current {
            background: #30cfd0;
            color: white;
            font-weight: 600;
        }

        .nav-button {
            padding: 0.5rem 1rem;
            background: #30cfd0;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 1rem;
        }

        .nav-button:hover {
            background: #2ab5b6;
            transform: translateY(-2px);
        }

        .exam-info {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .exam-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            color: #333;
        }

        /* 메인 컨테이너 */
        .main-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* 레벨 0 - 메인 대시보드 */
        .level-0 {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 100px);
        }

        .welcome-title {
            font-size: 3.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #30cfd0, #330867);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            text-align: center;
        }

        .welcome-subtitle {
            color: white;
            font-size: 1.25rem;
            margin-bottom: 3rem;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* 학년별 선택 */
        .grade-selector {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .grade-button {
            padding: 1rem 2rem;
            background: white;
            border: 3px solid transparent;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            font-size: 1.1rem;
            color: #333;
        }
        
        .grade-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .grade-button.active {
            border-color: #30cfd0;
            background: #30cfd0;
            color: white;
        }
        
        /* 과목 선택 */
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            max-width: 1000px;
            margin: 0 auto 2rem;
        }
        
        .subject-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 2px solid transparent;
        }
        
        .subject-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #30cfd0;
        }
        
        .subject-card h3 {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .subject-card p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .subject-card.selected {
            border-color: #30cfd0;
            background: #f0f9ff;
            position: relative;
        }
        
        .subject-card.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 15px;
            background: #30cfd0;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }

        .main-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .main-card {
            background: white;
            padding: 3rem;
            border-radius: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.5s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .main-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        }

        .main-card:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .main-card .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .main-card h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .main-card p {
            color: #666;
            font-size: 1rem;
        }

        /* 중간고사 카드 */
        .midterm-card {
            --gradient-start: #11998e;
            --gradient-end: #38ef7d;
        }

        /* 기말고사 카드 */
        .final-card {
            --gradient-start: #fc4a1a;
            --gradient-end: #f7b733;
        }

        /* 모의고사 카드 */
        .mock-card {
            --gradient-start: #4776e6;
            --gradient-end: #8e54e9;
        }
        
        /* 시험 정보 입력 폼 */
        .exam-info-form {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #30cfd0;
            box-shadow: 0 0 0 3px rgba(48, 207, 208, 0.1);
        }
        
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.5rem;
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
            transition: all 0.3s;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: #30cfd0;
            box-shadow: 0 0 0 3px rgba(48, 207, 208, 0.1);
        }
        
        .form-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .save-button {
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #30cfd0, #2ab5b6);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(48, 207, 208, 0.3);
        }
        
        .next-button {
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .next-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .info-hint {
            font-size: 0.875rem;
            color: #999;
            margin-top: 0.25rem;
        }
        
        .score-range {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .score-input {
            width: 100px;
        }

        /* 시험 일정 버튼 */
        .schedule-button {
            width: 100%;
            max-width: 965px;
            padding: 1.5rem;
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            border: none;
            border-radius: 1rem;
            color: #333;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.5s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .schedule-button:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        /* 레벨 1 - 시험 준비 */
        .level-1 {
            display: none;
        }

        .exam-title {
            text-align: center;
            color: white;
            font-size: 3rem;
            margin-bottom: 3rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .preparation-tabs {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .prep-tab {
            padding: 1rem 2rem;
            background: white;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            font-size: 1.1rem;
            border: 3px solid transparent;
        }

        .prep-tab:hover {
            transform: translateY(-2px);
        }

        .prep-tab.active {
            background: #30cfd0;
            color: white;
            border-color: white;
        }

        .content-area {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
        }

        /* 단원별 학습 */
        .unit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .unit-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            position: relative;
        }

        .unit-card:hover {
            border-color: #30cfd0;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .unit-progress {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 50px;
            height: 50px;
        }

        .progress-circle {
            fill: none;
            stroke: #e0e0e0;
            stroke-width: 3;
        }

        .progress-circle-fill {
            fill: none;
            stroke: #30cfd0;
            stroke-width: 3;
            stroke-dasharray: 126;
            stroke-dashoffset: 126;
            transform: rotate(-90deg);
            transform-origin: center;
            transition: stroke-dashoffset 0.5s;
        }

        .unit-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .unit-topics {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* 기출문제 */
        .past-exams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .exam-card {
            background: #f8f9fa;
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
        }

        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .exam-header {
            background: linear-gradient(135deg, #30cfd0, #330867);
            color: white;
            padding: 1.5rem;
        }

        .exam-year {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .exam-school {
            font-size: 1rem;
            opacity: 0.9;
        }

        .exam-body {
            padding: 1.5rem;
        }

        .exam-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 1rem;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #30cfd0;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #666;
        }

        /* 오답노트 */
        .notebook-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .mistake-list {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .mistake-item {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mistake-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .mistake-info {
            flex: 1;
        }

        .mistake-date {
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .mistake-problem {
            font-weight: 600;
            color: #333;
        }

        .mistake-status {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .status-review {
            background: #fff3cd;
            color: #856404;
        }

        .status-solved {
            background: #d4edda;
            color: #155724;
        }

        /* 레벨 2 - 시험 일정 */
        .level-2 {
            display: none;
        }

        .calendar-container {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 1000px;
            margin: 0 auto;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .calendar-title {
            font-size: 2rem;
            color: #333;
        }

        .calendar-nav {
            display: flex;
            gap: 1rem;
        }

        .calendar-button {
            padding: 0.5rem 1rem;
            background: #30cfd0;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: bold;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .calendar-day:hover {
            background: #f0f0f0;
        }

        .calendar-day.has-exam {
            background: #ffe0e0;
            color: #d32f2f;
        }

        .calendar-day.selected {
            background: #30cfd0;
            color: white;
        }

        .exam-indicator {
            position: absolute;
            bottom: 5px;
            width: 6px;
            height: 6px;
            background: #d32f2f;
            border-radius: 50%;
        }

        /* 시험 상세 정보 */
        .exam-details {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 1rem;
        }

        .exam-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .exam-detail-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .exam-countdown {
            font-size: 1.25rem;
            color: #d32f2f;
            font-weight: bold;
        }

        /* 애니메이션 */
        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 반응형 */
        @media (max-width: 1024px) {
            .main-cards {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            
            .notebook-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-cards {
                grid-template-columns: 1fr !important;
            }
            
            .welcome-title {
                font-size: 2.5rem;
            }
            
            .preparation-tabs {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .score-range {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .score-input {
                width: 100%;
            }
            
            .form-buttons {
                flex-direction: column;
            }
            
            .save-button, .next-button {
                width: 100%;
            }
        }
        
        /* Tooltip 스타일 */
        .tooltip-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        
        .tooltip-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #3b82f6;
            color: white;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 5px;
            vertical-align: middle;
        }
        
        .tooltip-content {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            min-width: 200px;
            max-width: 300px;
            z-index: 1000;
            transition: opacity 0.3s, visibility 0.3s;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            line-height: 1.5;
        }
        
        .tooltip-content::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        
        .tooltip-container:hover .tooltip-content {
            visibility: visible;
            opacity: 1;
        }
        
        /* 서술평가 이미지 스타일 */
        .essay-icon {
            width: 16px;
            height: 16px;
            display: inline-block;
            vertical-align: middle;
            margin-left: 5px;
        }
        
        /* 서술평가 강조 스타일 */
        .essay-highlight {
            background: #fef3c7;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        /* 이미지 뷰어 스타일 */
        .image-viewer-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            overflow: auto;
        }
        
        .image-viewer-modal.active {
            display: flex;
            flex-direction: column;
        }
        
        .viewer-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10001;
        }
        
        .viewer-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .viewer-btn {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .viewer-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .viewer-btn.active {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        
        .viewer-content {
            flex: 1;
            padding: 2rem;
            overflow: auto;
        }
        
        /* 탭 모드 스타일 */
        .tab-mode-container {
            display: none;
            height: 100%;
        }
        
        .tab-mode-container.active {
            display: flex;
            flex-direction: column;
        }
        
        .problem-tabs {
            display: flex;
            gap: 0.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            overflow-x: auto;
        }
        
        .problem-tab {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            border-radius: 0.5rem 0.5rem 0 0;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s;
        }
        
        .problem-tab:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .problem-tab.active {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .tab-content {
            flex: 1;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .tab-content.active {
            display: flex;
        }
        
        /* 스크롤 모드 스타일 */
        .scroll-mode-container {
            display: none;
            padding: 2rem;
        }
        
        .scroll-mode-container.active {
            display: block;
        }
        
        .problem-item {
            margin-bottom: 3rem;
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            position: relative;
        }
        
        .problem-number {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: #3b82f6;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: bold;
        }
        
        .problem-image {
            width: 100%;
            max-width: 50vw;
            margin: 0 auto;
            display: block;
            cursor: zoom-in;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .problem-image.fullscreen {
            max-width: 90vw;
            max-height: 90vh;
            cursor: zoom-out;
        }
        
        /* 다크 모드 스타일 */
        body.dark-mode {
            background: #1a1a1a;
            color: #e0e0e0;
        }
        
        body.dark-mode .problem-item {
            background: #2a2a2a;
            color: #e0e0e0;
        }
        
        body.dark-mode .viewer-header {
            background: rgba(0, 0, 0, 0.8);
        }
        
        body.dark-mode .problem-tab {
            background: rgba(0, 0, 0, 0.3);
        }
        
        body.dark-mode .problem-tab.active {
            background: rgba(0, 0, 0, 0.5);
        }
        
        /* 라이트 모드 (이미지 뷰어) */
        .image-viewer-modal.light-mode {
            background: rgba(255, 255, 255, 0.95);
        }
        
        .image-viewer-modal.light-mode .viewer-header {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }
        
        .image-viewer-modal.light-mode .viewer-btn {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
            border-color: rgba(0, 0, 0, 0.2);
        }
        
        .image-viewer-modal.light-mode .viewer-btn:hover {
            background: rgba(0, 0, 0, 0.2);
        }
        
        .image-viewer-modal.light-mode .problem-item {
            background: white;
            color: #333;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .image-viewer-modal.light-mode .problem-tab {
            background: rgba(0, 0, 0, 0.05);
            color: #333;
        }
        
        .image-viewer-modal.light-mode .problem-tab.active {
            background: white;
        }
        
        /* 전체화면 모드 */
        .fullscreen-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 20000;
            justify-content: center;
            align-items: center;
            cursor: zoom-out;
        }
        
        .fullscreen-overlay.active {
            display: flex;
        }
        
        .fullscreen-image {
            max-width: 90vw;
            max-height: 90vh;
            object-fit: contain;
        }
        
        .close-fullscreen {
            position: absolute;
            top: 2rem;
            right: 2rem;
            width: 3rem;
            height: 3rem;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .close-fullscreen:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        /* 문제 이미지 썸네일 */
        .problem-thumbnail {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0.5rem;
        }
        
        .problem-thumbnail:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <!-- 네비게이션 바 -->
    <nav class="navbar">
    <div style="display: flex; gap: 1rem;">
            <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php" class="nav-button">
                🏠 홈
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/index42.php" class="nav-button">
            🧠 내공부방
            </a>


            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/today42.php" class="nav-button">
             🧭 오늘
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/schedule42.php" class="nav-button">
                📊 일정
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/goals42.php" class="nav-button">
                🎯 목표
            </a>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/timescaffolding42.php" class="nav-button">
                📅 수학일기
            </a>
        </div>
        
        <div class="exam-info">
            <div class="exam-stat">
                <span>📅</span>
                <span id="days-until">D-30</span>
            </div>
            <div class="exam-stat">
                <span>📊</span>
                <span id="avg-score">평균 85점</span>
            </div>
            <div class="exam-stat">
                <span>✅</span>
                <span id="completion">진도 70%</span>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; position: relative;">
            <button class="nav-button" onclick="toggleNotebook()">
                📔 오답노트
            </button>
            <button class="minimap-button" onclick="toggleMinimap()">
                🗺️ 미니맵
            </button>
            <div class="minimap-dropdown" id="minimapDropdown">
                <h3 class="minimap-title">
                    <span>🗺️</span>
                    <span>학습 목차</span>
                </h3>
                <a href="index.php" class="minimap-item">
                    <span>🏠</span>
                    <span>메인 홈</span>
                </a>
                <a href="index1.php" class="minimap-item">
                    <span>📚</span>
                    <span>개념학습</span>
                </a>
                <a href="index2.php" class="minimap-item">
                    <span>🎯</span>
                    <span>심화학습</span>
                </a>
                <a href="index3.php" class="minimap-item current">
                    <span>📝</span>
                    <span>내신준비</span>
                </a>
                <a href="index4.php" class="minimap-item">
                    <span>🎓</span>
                    <span>수능대비</span>
                </a>
                <a href="indexm.php" class="minimap-item">
                    <span>🧠</span>
                    <span>메타인지</span>
                </a>
            </div>
        </div>
    </nav>
    

    <!-- 메인 컨테이너 -->
    <div class="main-container" id="main-container">
        <!-- 레벨 0: 메인 대시보드 -->
        <div class="level-0" id="level-0">
            <h1 class="welcome-title">내신준비 센터</h1>
            <p class="welcome-subtitle">학교 시험을 완벽하게 대비하세요</p>
            
            <!-- 학년별 선택 -->
            <div class="grade-selector">
                <button class="grade-button active" onclick="selectGrade('elementary')">초등수학</button>
                <button class="grade-button" onclick="selectGrade('middle')">중등수학</button>
                <button class="grade-button" onclick="selectGrade('high')">고등수학</button>
            </div>
            
            <!-- 과목 선택 -->
            <div class="subject-grid" id="subject-grid">
                <!-- 동적으로 생성됨 -->
            </div>
            
            <!-- 시험 유형 선택 -->
            <div class="exam-types" id="exam-types">
                <!-- 학년에 따라 동적으로 생성됨 -->
            </div>
            
            <button class="schedule-button" onclick="navigateToExamInfo()">
                <span>📝</span>
                <span>시험일정관리 및 시험관련 정보 수집</span>
                <span>📊</span>
            </button>
        </div>

        <!-- 레벨 1: 시험 준비 -->
        <div class="level-1" id="level-1">
            <h1 class="exam-title" id="exam-title">중간고사 대비</h1>
            
            <div class="preparation-tabs">
                <div class="prep-tab active" onclick="showContent('units')">
                    단원별 학습
                </div>
                <div class="prep-tab" onclick="showContent('past')">
                    기출문제
                </div>
                <div class="prep-tab" onclick="showContent('mistakes')">
                    오답노트
                </div>
            </div>
            
            <div class="content-area">
                <!-- 단원별 학습 -->
                <div id="units-content" class="content-section">
                    <h3 style="margin-bottom: 1.5rem; color: #333;">학습 진도</h3>
                    <div class="unit-grid">
                        <div class="unit-card" onclick="startUnit(0)">
                            <svg class="unit-progress" viewBox="0 0 50 50">
                                <circle class="progress-circle" cx="25" cy="25" r="20"></circle>
                                <circle class="progress-circle-fill" cx="25" cy="25" r="20" 
                                        style="stroke-dashoffset: 50;"></circle>
                                <text x="25" y="25" text-anchor="middle" dominant-baseline="middle" 
                                      font-size="12" font-weight="bold">60%</text>
                            </svg>
                            <div class="unit-title">1단원: 수와 연산</div>
                            <div class="unit-topics">
                                • 유리수와 순환소수<br>
                                • 단항식과 다항식<br>
                                • 일차방정식
                            </div>
                        </div>
                        
                        <div class="unit-card" onclick="startUnit(1)">
                            <svg class="unit-progress" viewBox="0 0 50 50">
                                <circle class="progress-circle" cx="25" cy="25" r="20"></circle>
                                <circle class="progress-circle-fill" cx="25" cy="25" r="20" 
                                        style="stroke-dashoffset: 76;"></circle>
                                <text x="25" y="25" text-anchor="middle" dominant-baseline="middle" 
                                      font-size="12" font-weight="bold">40%</text>
                            </svg>
                            <div class="unit-title">2단원: 함수</div>
                            <div class="unit-topics">
                                • 함수의 개념<br>
                                • 일차함수<br>
                                • 일차함수의 활용
                            </div>
                        </div>
                        
                        <div class="unit-card" onclick="startUnit(2)">
                            <svg class="unit-progress" viewBox="0 0 50 50">
                                <circle class="progress-circle" cx="25" cy="25" r="20"></circle>
                                <circle class="progress-circle-fill" cx="25" cy="25" r="20" 
                                        style="stroke-dashoffset: 25;"></circle>
                                <text x="25" y="25" text-anchor="middle" dominant-baseline="middle" 
                                      font-size="12" font-weight="bold">80%</text>
                            </svg>
                            <div class="unit-title">3단원: 통계</div>
                            <div class="unit-topics">
                                • 도수분포표<br>
                                • 히스토그램<br>
                                • 평균과 분산
                            </div>
                        </div>
                        
                        <div class="unit-card" onclick="startUnit(3)">
                            <svg class="unit-progress" viewBox="0 0 50 50">
                                <circle class="progress-circle" cx="25" cy="25" r="20"></circle>
                                <circle class="progress-circle-fill" cx="25" cy="25" r="20" 
                                        style="stroke-dashoffset: 126;"></circle>
                                <text x="25" y="25" text-anchor="middle" dominant-baseline="middle" 
                                      font-size="12" font-weight="bold">0%</text>
                            </svg>
                            <div class="unit-title">4단원: 도형</div>
                            <div class="unit-topics">
                                • 삼각형의 성질<br>
                                • 사각형의 성질<br>
                                • 도형의 닮음
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 기출문제 -->
                <div id="past-content" class="content-section" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="color: #333; margin: 0;">최근 기출문제</h3>
                        <button class="viewer-btn" style="background: #3b82f6; color: white; border: none;" onclick="openImageViewer()">
                            🖼️ 문제 이미지 보기
                        </button>
                    </div>
                    <div class="past-exams-grid">
                        <div class="exam-card" onclick="startPastExam(0)">
                            <div class="exam-header">
                                <div class="exam-year">2024년 1학기</div>
                                <div class="exam-school">서울중학교</div>
                            </div>
                            <div class="exam-body">
                                <div class="exam-stats">
                                    <div class="stat">
                                        <div class="stat-value">25</div>
                                        <div class="stat-label">문항</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-value">50분</div>
                                        <div class="stat-label">시간</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-value">82.5</div>
                                        <div class="stat-label">평균</div>
                                    </div>
                                </div>
                                <button class="nav-button" style="width: 100%; margin-top: 1rem;">
                                    시작하기
                                </button>
                            </div>
                        </div>
                        
                        <div class="exam-card" onclick="startPastExam(1)">
                            <div class="exam-header">
                                <div class="exam-year">2023년 2학기</div>
                                <div class="exam-school">서울중학교</div>
                            </div>
                            <div class="exam-body">
                                <div class="exam-stats">
                                    <div class="stat">
                                        <div class="stat-value">30</div>
                                        <div class="stat-label">문항</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-value">60분</div>
                                        <div class="stat-label">시간</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-value">78.3</div>
                                        <div class="stat-label">평균</div>
                                    </div>
                                </div>
                                <button class="nav-button" style="width: 100%; margin-top: 1rem;">
                                    시작하기
                                </button>
                            </div>
                        </div>
                        
                        <div class="exam-card" onclick="startPastExam(2)">
                            <div class="exam-header">
                                <div class="exam-year">2023년 1학기</div>
                                <div class="exam-school">서울중학교</div>
                            </div>
                            <div class="exam-body">
                                <div class="exam-stats">
                                    <div class="stat">
                                        <div class="stat-value">28</div>
                                        <div class="stat-label">문항</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-value">50분</div>
                                        <div class="stat-label">시간</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-value">85.7</div>
                                        <div class="stat-label">평균</div>
                                    </div>
                                </div>
                                <button class="nav-button" style="width: 100%; margin-top: 1rem;">
                                    시작하기
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 오답노트 -->
                <div id="mistakes-content" class="content-section" style="display: none;">
                    <h3 style="margin-bottom: 1.5rem; color: #333;">오답 관리</h3>
                    <div class="notebook-section">
                        <div>
                            <h4 style="margin-bottom: 1rem; color: #666;">최근 오답</h4>
                            <div class="mistake-list">
                                <div class="mistake-item" onclick="reviewMistake(0)">
                                    <div class="mistake-info">
                                        <div class="mistake-date">2024.11.15</div>
                                        <div class="mistake-problem">일차함수의 그래프 #12</div>
                                    </div>
                                    <span class="mistake-status status-review">복습필요</span>
                                </div>
                                
                                <div class="mistake-item" onclick="reviewMistake(1)">
                                    <div class="mistake-info">
                                        <div class="mistake-date">2024.11.14</div>
                                        <div class="mistake-problem">연립방정식의 활용 #8</div>
                                    </div>
                                    <span class="mistake-status status-solved">해결완료</span>
                                </div>
                                
                                <div class="mistake-item" onclick="reviewMistake(2)">
                                    <div class="mistake-info">
                                        <div class="mistake-date">2024.11.13</div>
                                        <div class="mistake-problem">도수분포표 작성 #5</div>
                                    </div>
                                    <span class="mistake-status status-review">복습필요</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="margin-bottom: 1rem; color: #666;">오답 통계</h4>
                            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                    <div style="text-align: center;">
                                        <div style="font-size: 2rem; font-weight: bold; color: #30cfd0;">23</div>
                                        <div style="color: #666;">전체 오답</div>
                                    </div>
                                    <div style="text-align: center;">
                                        <div style="font-size: 2rem; font-weight: bold; color: #11998e;">15</div>
                                        <div style="color: #666;">해결 완료</div>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 1rem;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span>해결률</span>
                                        <span style="font-weight: bold;">65%</span>
                                    </div>
                                    <div style="background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                                        <div style="background: #30cfd0; height: 100%; width: 65%; transition: width 0.5s;"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h5 style="margin-bottom: 0.5rem; color: #666;">취약 단원</h5>
                                    <div style="font-size: 0.9rem; color: #999;">
                                        1. 일차함수 (8문제)<br>
                                        2. 연립방정식 (5문제)<br>
                                        3. 통계 (3문제)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 레벨 2: 시험 정보 입력 -->
        <div class="level-2" id="level-2">
            <div class="exam-info-form">
                <h2 style="text-align: center; color: #333; margin-bottom: 2rem;">시험 정보 입력</h2>
                
                <!-- 1. 시험범위 -->
                <div class="form-section" id="section-1" style="display: block;">
                    <h3 class="form-title">
                        <span>📚</span>
                        <span>1. 시험범위</span>
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">과목:</label>
                        <input type="text" class="form-input" id="subject" placeholder="예: 수학, 영어, 국어 등">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">범위 설명:</label>
                        <textarea class="form-textarea" id="exam-range" placeholder="예: 1단원 ~ 4단원, 교과서 p.10 ~ p.150"></textarea>
                    </div>
                    
                    <div class="form-buttons">
                        <button class="next-button" onclick="nextSection(2)">다음</button>
                    </div>
                </div>
                
                <!-- 2. 시험일정 -->
                <div class="form-section" id="section-2" style="display: none;">
                    <h3 class="form-title">
                        <span>📅</span>
                        <span>2. 시험일정</span>
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">시험 날짜/시간:</label>
                        <input type="datetime-local" class="form-input" id="exam-datetime">
                        <p class="info-hint">시험 날짜와 시작 시간을 입력하세요</p>
                    </div>
                    
                    <div class="form-buttons">
                        <button class="save-button" onclick="prevSection(1)">이전</button>
                        <button class="next-button" onclick="nextSection(3)">다음</button>
                    </div>
                </div>
                
                <!-- 3. 하루 전 등원 시간 및 공부 계획 -->
                <div class="form-section" id="section-3" style="display: none;">
                    <h3 class="form-title">
                        <span>⏰</span>
                        <span>3. 하루 전 등원 시간 및 공부 계획</span>
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">등원 시간:</label>
                        <input type="time" class="form-input" id="arrival-time">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">공부 시간:</label>
                        <input type="text" class="form-input" id="study-time" placeholder="예: 오후 2시 ~ 6시, 총 4시간">
                    </div>
                    
                    <div class="form-buttons">
                        <button class="save-button" onclick="prevSection(2)">이전</button>
                        <button class="next-button" onclick="nextSection(4)">다음</button>
                    </div>
                </div>
                
                <!-- 4. 공부재료 -->
                <div class="form-section" id="section-4" style="display: none;">
                    <h3 class="form-title">
                        <span>📖</span>
                        <span>4. 공부재료</span>
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">4-1. 책 제목:</label>
                        <textarea class="form-textarea" id="book-titles" placeholder="예:
- 개념원리 수학 상
- 쎈 수학 상
- 교과서"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">4-2. 온라인 콘텐츠:</label>
                        <textarea class="form-textarea" id="online-content" placeholder="예:
- EBS 수학 강의
- 유튜브 수학 채널
- 온라인 문제은행"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">4-3. 학교 프린트물:</label>
                        <textarea class="form-textarea" id="school-prints" placeholder="예:
- 단원 정리 프린트
- 보충 문제
- 선생님 제작 자료"></textarea>
                    </div>
                    
                    <div class="form-buttons">
                        <button class="save-button" onclick="prevSection(3)">이전</button>
                        <button class="next-button" onclick="nextSection(5)">다음</button>
                    </div>
                </div>
                
                <!-- 5. 시험관련 학교 선생님의 발언 / 소문 -->
                <div class="form-section" id="section-5" style="display: none;">
                    <h3 class="form-title">
                        <span>💬</span>
                        <span>5. 시험관련 학교 선생님의 발언 / 소문</span>
                    </h3>
                    
                    <div class="form-group">
                        <textarea class="form-textarea" id="teacher-comments" placeholder="예:
- 선생님이 3단원 특히 중요하다고 하심
- 작년 기출문제와 유사하게 출제 예정
- 서술형 문제 비중이 높을 것
- 교과서 예제 문제 꼭 풀어보라고 하심"></textarea>
                        <div style="margin-top: 10px;">
                            <span class="essay-highlight">서술형 평가</span>
                            <span class="tooltip-container">
                                <span class="tooltip-icon">?</span>
                                <span class="tooltip-content">
                                    서술형 평가는 문제 해결 과정을 자세히 기술하는 평가입니다. 
                                    풀이 과정, 논리적 전개, 수식 표현이 중요합니다.
                                </span>
                            </span>
                            <span class="tooltip-container" style="margin-left: 10px;">
                                <span class="tooltip-icon">📝</span>
                                <span class="tooltip-content">
                                    서술형 답안 작성 팁:<br>
                                    • 문제를 정확히 이해하고 조건을 명시<br>
                                    • 풀이 과정을 단계별로 작성<br>
                                    • 수식과 설명을 함께 기술<br>
                                    • 최종 답에 밑줄 표시
                                </span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button class="save-button" onclick="prevSection(4)">이전</button>
                        <button class="next-button" onclick="nextSection(6)">다음</button>
                    </div>
                </div>
                
                <!-- 6. 출제경향 -->
                <div class="form-section" id="section-6" style="display: none;">
                    <h3 class="form-title">
                        <span>📊</span>
                        <span>6. 출제경향 (알고 있는 사실)</span>
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">주요 유형:</label>
                        <textarea class="form-textarea" id="main-types" placeholder="예:
- 계산 문제 40%
- 개념 이해 문제 30%
- 응용 문제 20%
- 서술형 10%"></textarea>
                        <div style="margin-top: 10px;">
                            <span class="essay-highlight">서술형 평가 예시</span>
                            <span class="tooltip-container">
                                <span class="tooltip-icon">💡</span>
                                <span class="tooltip-content">
                                    서술형 문제 예시:<br>
                                    • "다음 문제를 풀고, 풀이 과정을 자세히 서술하시오."<br>
                                    • "주어진 조건을 만족하는 값을 구하고, 그 이유를 설명하시오."<br>
                                    • "두 방법의 차이점을 비교하여 설명하시오."
                                </span>
                            </span>
                            <span class="tooltip-container" style="margin-left: 10px;">
                                <span class="tooltip-icon">⚠️</span>
                                <span class="tooltip-content">
                                    서술형 평가 주의사항:<br>
                                    • 부분 점수가 있으므로 최대한 작성<br>
                                    • 단계별 풀이 과정 필수<br>
                                    • 깔끔한 글씨와 정리된 답안지<br>
                                    • 시간 배분에 유의 (전체 시간의 30% 할애)
                                </span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">최근 트렌드:</label>
                        <textarea class="form-textarea" id="recent-trends" placeholder="예:
- 실생활 연계 문제 증가
- 통합적 사고력 문제 출제
- 과정 중심 평가 강화"></textarea>
                    </div>
                    
                    <div class="form-buttons">
                        <button class="save-button" onclick="prevSection(5)">이전</button>
                        <button class="next-button" onclick="nextSection(7)">다음</button>
                    </div>
                </div>
                
                <!-- 7. 목표점수 -->
                <div class="form-section" id="section-7" style="display: none;">
                    <h3 class="form-title">
                        <span>🎯</span>
                        <span>7. 최소 목표점수 ~ 최대 목표점수</span>
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">목표 점수 범위:</label>
                        <div class="score-range">
                            <input type="number" class="form-input score-input" id="min-score" placeholder="최소" min="0" max="100">
                            <span>점 ~</span>
                            <input type="number" class="form-input score-input" id="max-score" placeholder="최대" min="0" max="100">
                            <span>점</span>
                        </div>
                        <p class="info-hint">현실적이면서도 도전적인 목표를 설정하세요</p>
                    </div>
                    
                    <div class="form-buttons">
                        <button class="save-button" onclick="prevSection(6)">이전</button>
                        <button class="next-button" onclick="saveExamInfo()" style="background: linear-gradient(135deg, #11998e, #38ef7d);">저장하기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/save_selection.js"></script>
    <script>
        // 전역 변수
        let currentLevel = 0;
        let currentPath = [];
        let currentExamType = '';
        
        // 사용자 정보
        const studentId = <?php echo $studentid; ?>;
        const pageType = 'index3';
        let currentContent = 'units';
        let selectedDate = null;
        let currentSection = 1;
        
        // 시험 정보 저장 객체
        let examInfo = {
            subject: '',
            examRange: '',
            examDatetime: '',
            arrivalTime: '',
            studyTime: '',
            bookTitles: '',
            onlineContent: '',
            schoolPrints: '',
            teacherComments: '',
            mainTypes: '',
            recentTrends: '',
            minScore: '',
            maxScore: ''
        };
        
        // 학년별 과목 데이터
        const gradeSubjects = {
            elementary: {
                title: '초등 내신수학',
                subjects: [
                    { code: '4-1', name: '초등수학 4-1', desc: '4학년 1학기 내신' },
                    { code: '4-2', name: '초등수학 4-2', desc: '4학년 2학기 내신' },
                    { code: '5-1', name: '초등수학 5-1', desc: '5학년 1학기 내신' },
                    { code: '5-2', name: '초등수학 5-2', desc: '5학년 2학기 내신' },
                    { code: '6-1', name: '초등수학 6-1', desc: '6학년 1학기 내신' },
                    { code: '6-2', name: '초등수학 6-2', desc: '6학년 2학기 내신' }
                ]
            },
            middle: {
                title: '중등 내신수학',
                subjects: [
                    { code: '1-1', name: '중등수학 1-1', desc: '중학교 1학년 1학기' },
                    { code: '1-2', name: '중등수학 1-2', desc: '중학교 1학년 2학기' },
                    { code: '2-1', name: '중등수학 2-1', desc: '중학교 2학년 1학기' },
                    { code: '2-2', name: '중등수학 2-2', desc: '중학교 2학년 2학기' },
                    { code: '3-1', name: '중등수학 3-1', desc: '중학교 3학년 1학기' },
                    { code: '3-2', name: '중등수학 3-2', desc: '중학교 3학년 2학기' }
                ]
            },
            high: {
                title: '고등 내신수학',
                subjects: [
                    { code: 'common1', name: '공통수학 1', desc: '고등학교 공통 과정' },
                    { code: 'common2', name: '공통수학 2', desc: '고등학교 공통 과정' },
                    { code: 'algebra', name: '대수', desc: '선택 과목' },
                    { code: 'calculus1', name: '미적분 I', desc: '선택 과목' },
                    { code: 'statistics', name: '확률과 통계', desc: '선택 과목' },
                    { code: 'calculus2', name: '미적분 II', desc: '선택 과목' },
                    { code: 'geometry', name: '기하', desc: '선택 과목' }
                ]
            }
        };
        
        let currentGrade = 'elementary';
        
        // 시험 데이터
        const examDates = {
            '2024-11-20': { type: '중간고사', subject: '수학' },
            '2024-11-25': { type: '영어시험', subject: '영어' },
            '2024-12-15': { type: '기말고사', subject: '전과목' },
            '2024-12-05': { type: '모의고사', subject: '수학' }
        };

        // 초기화
        window.onload = function() {
            updateExamInfo();
            generateCalendar();
            showSubjects(currentGrade);
            showExamTypes(currentGrade);
            
            // 마지막 선택 복원
            <?php if ($should_restore && $last_selection): ?>
            const lastData = <?php echo json_encode(json_decode($last_selection->selection_data, true)); ?>;
            if (lastData) {
                // 과목 선택 바로 이동 (direct=true일 때)
                if (lastData.path === 'subject_selection' && lastData.subjectCode) {
                    <?php if ($direct_to_study): ?>
                    // 바로 mathking.kr로 이동
                    if (lastData.grade === 'elementary') {
                        const cidMap = {
                            '4-1': 73, '4-2': 74, '5-1': 75, 
                            '5-2': 76, '6-1': 78, '6-2': 79
                        };
                        if (cidMap[lastData.subjectCode]) {
                            window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?&cid=${cidMap[lastData.subjectCode]}&type=init`;
                        }
                    } else if (lastData.grade === 'middle') {
                        const cidMap = {
                            '1-1': 42, '1-2': 43, '2-1': 44,
                            '2-2': 45, '3-1': 46, '3-2': 47
                        };
                        if (cidMap[lastData.subjectCode]) {
                            window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=3&cid=${cidMap[lastData.subjectCode]}&tb=90`;
                        }
                    } else if (lastData.grade === 'high') {
                        const cidMap = {
                            'common1': 2, 'common2': 36, 'algebra': 37,
                            'calculus1': 38, 'statistics': 40,
                            'calculus2': 39, 'geometry': 41
                        };
                        if (cidMap[lastData.subjectCode]) {
                            window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=3&cid=${cidMap[lastData.subjectCode]}&tb=90`;
                        }
                    }
                    <?php endif; ?>
                }
                // 단원 학습이었던 경우
                else if (lastData.path === 'units' && lastData.unitIndex !== undefined) {
                    // 먼저 시험 유형을 설정하고 레벨 1로 이동
                    currentExamType = lastData.examType || '중간고사';
                    document.getElementById('exam-title').textContent = currentExamType + ' 대비';
                    navigateToLevel(1);
                    
                    <?php if ($direct_to_study): ?>
                    // 직접 단원 학습으로 이동
                    setTimeout(() => {
                        const unitCards = document.querySelectorAll('.unit-card');
                        if (unitCards[lastData.unitIndex]) {
                            unitCards[lastData.unitIndex].click();
                        }
                    }, 800);
                    <?php else: ?>
                    setTimeout(() => {
                        const unitCards = document.querySelectorAll('.unit-card');
                        if (unitCards[lastData.unitIndex]) {
                            unitCards[lastData.unitIndex].style.border = '2px solid #30cfd0';
                            unitCards[lastData.unitIndex].style.boxShadow = '0 0 10px rgba(48, 207, 208, 0.3)';
                            
                            // 최근 라벨 추가
                            const label = document.createElement('div');
                            label.style.position = 'absolute';
                            label.style.top = '-10px';
                            label.style.right = '-10px';
                            label.style.background = '#30cfd0';
                            label.style.color = 'white';
                            label.style.padding = '2px 8px';
                            label.style.borderRadius = '12px';
                            label.style.fontSize = '0.75rem';
                            label.style.fontWeight = 'bold';
                            label.textContent = '최근';
                            unitCards[lastData.unitIndex].style.position = 'relative';
                            unitCards[lastData.unitIndex].appendChild(label);
                        }
                    }, 500);
                    <?php endif; ?>
                }
                
                // 시험 유형 설정
                if (lastData.examType) {
                    currentExamType = lastData.examType;
                }
            }
            <?php endif; ?>
        };

        // 시험 정보 업데이트
        function updateExamInfo() {
            // D-day 계산
            const today = new Date();
            const nextExam = new Date('2024-11-20');
            const daysUntil = Math.ceil((nextExam - today) / (1000 * 60 * 60 * 24));
            document.getElementById('days-until').textContent = `D-${daysUntil}`;
            
            // 평균 점수 (로컬 스토리지에서 불러오기)
            const avgScore = localStorage.getItem('avgScore') || 85;
            document.getElementById('avg-score').textContent = `평균 ${avgScore}점`;
            
            // 진도율
            const completion = localStorage.getItem('completion') || 70;
            document.getElementById('completion').textContent = `진도 ${completion}%`;
        }

        // 네비게이션
        function navigateToExam(examType) {
            if (selectedSubject === null) {
                alert('먼저 과목을 선택해주세요!');
                // 과목 선택 영역으로 스크롤
                document.getElementById('subject-grid').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
                return;
            }
            
            currentExamType = examType;
            currentPath = [examType];
            document.getElementById('exam-title').textContent = examType + ' 대비';
            
            // 시험 유형 선택 시 저장
            console.log('Saving exam type selection:', {
                pageType: pageType,
                examType: examType
            });
            
            saveUserSelection(pageType, examType, '시험 유형 선택', {
                examType: examType,
                path: 'exam_type'
            });
            
            navigateToLevel(1);
        }

        function navigateToExamInfo() {
            currentPath = ['시험 정보 입력'];
            currentSection = 1;
            navigateToLevel(2);
            showSection(1);
        }

        function navigateToLevel(level) {
            document.getElementById(`level-${currentLevel}`).style.display = 'none';
            currentLevel = level;
            
            const newLevel = document.getElementById(`level-${level}`);
            newLevel.style.display = level === 0 ? 'flex' : 'block';
            newLevel.classList.add('fade-in');
            
            document.getElementById('back-button').style.display = level > 0 ? 'flex' : 'none';
        }

        function goBack() {
            if (currentLevel > 0) {
                navigateToLevel(currentLevel - 1);
                currentPath.pop();
            }
        }

        // 콘텐츠 전환
        function showContent(type) {
            // 탭 활성화
            document.querySelectorAll('.prep-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 콘텐츠 전환
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(`${type}-content`).style.display = 'block';
            
            currentContent = type;
        }

        // 단원 학습 시작
        function startUnit(unitIndex) {
            const units = ['수와 연산', '함수', '통계', '도형'];
            const unitName = units[unitIndex];
            
            // 선택 정보 저장
            console.log('Saving selection for index3:', {
                pageType: pageType,
                unitName: unitName,
                unitIndex: unitIndex
            });
            
            saveUserSelection(pageType, unitName, `unit-${unitIndex}`, {
                unitIndex: unitIndex,
                unitName: unitName,
                examType: currentExamType || '중간고사',
                path: 'units'
            });
            
            alert(`${unitName} 단원 학습을 시작합니다!`);
            
            // 진도 업데이트
            updateProgress(unitIndex);
        }

        // 진도 업데이트
        function updateProgress(unitIndex) {
            const progressValues = [60, 40, 80, 0];
            const newProgress = Math.min(progressValues[unitIndex] + 10, 100);
            
            // SVG 애니메이션 업데이트
            const circles = document.querySelectorAll('.progress-circle-fill');
            const circumference = 2 * Math.PI * 20;
            const offset = circumference - (newProgress / 100) * circumference;
            
            if (circles[unitIndex]) {
                circles[unitIndex].style.strokeDashoffset = offset;
                const text = circles[unitIndex].parentElement.querySelector('text');
                text.textContent = newProgress + '%';
            }
            
            // 전체 진도 업데이트
            const totalProgress = Math.round((newProgress + 40 + 80 + 0) / 4);
            document.getElementById('completion').textContent = `진도 ${totalProgress}%`;
            localStorage.setItem('completion', totalProgress);
        }

        // 기출문제 시작
        function startPastExam(examIndex) {
            const exams = ['2024년 1학기', '2023년 2학기', '2023년 1학기'];
            alert(`${exams[examIndex]} 기출문제를 시작합니다!`);
        }

        // 오답 복습
        function reviewMistake(mistakeIndex) {
            const mistakes = ['일차함수의 그래프', '연립방정식의 활용', '도수분포표 작성'];
            alert(`${mistakes[mistakeIndex]} 문제를 복습합니다!`);
        }

        // 오답노트 토글
        function toggleNotebook() {
            if (currentLevel === 1 && currentContent !== 'mistakes') {
                showContent('mistakes');
                document.querySelectorAll('.prep-tab')[2].classList.add('active');
            }
        }

        // 캘린더 생성
        function generateCalendar() {
            const grid = document.getElementById('calendar-grid');
            const year = 2024;
            const month = 10; // 11월 (0부터 시작)
            
            // 요일 헤더
            const days = ['일', '월', '화', '수', '목', '금', '토'];
            days.forEach(day => {
                const dayElement = document.createElement('div');
                dayElement.style.textAlign = 'center';
                dayElement.style.fontWeight = 'bold';
                dayElement.style.color = '#666';
                dayElement.textContent = day;
                grid.appendChild(dayElement);
            });
            
            // 날짜 생성
            const firstDay = new Date(year, month, 1).getDay();
            const lastDate = new Date(year, month + 1, 0).getDate();
            
            // 빈 칸
            for (let i = 0; i < firstDay; i++) {
                const emptyDay = document.createElement('div');
                grid.appendChild(emptyDay);
            }
            
            // 날짜
            for (let date = 1; date <= lastDate; date++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                dayElement.textContent = date;
                
                const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
                
                if (examDates[dateString]) {
                    dayElement.classList.add('has-exam');
                    const indicator = document.createElement('div');
                    indicator.className = 'exam-indicator';
                    dayElement.appendChild(indicator);
                }
                
                dayElement.onclick = () => selectDate(dateString, date);
                grid.appendChild(dayElement);
            }
        }

        // 날짜 선택
        function selectDate(dateString, date) {
            // 이전 선택 제거
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.classList.remove('selected');
            });
            
            // 새 선택
            event.target.classList.add('selected');
            selectedDate = dateString;
            
            // 시험 정보 표시
            if (examDates[dateString]) {
                const exam = examDates[dateString];
                document.getElementById('exam-detail-title').textContent = exam.type;
                
                // D-day 계산
                const today = new Date();
                const examDate = new Date(dateString);
                const daysUntil = Math.ceil((examDate - today) / (1000 * 60 * 60 * 24));
                document.getElementById('exam-countdown').textContent = `D-${daysUntil}`;
                
                document.getElementById('exam-details').style.display = 'block';
            } else {
                document.getElementById('exam-details').style.display = 'none';
            }
        }

        // 월 이동
        function prevMonth() {
            alert('이전 달로 이동');
        }

        function nextMonth() {
            alert('다음 달로 이동');
        }

        // 시험 대비 시작
        function startPreparation() {
            if (selectedDate && examDates[selectedDate]) {
                const exam = examDates[selectedDate];
                navigateToExam(exam.type);
            }
        }
        
        // 시험 유형 데이터
        const examTypes = {
            elementary: [
                { type: '단원평가', icon: '📝', desc: '단원별 평가 대비' }
            ],
            middle: [],
            high: []
        };
        
        // 학년 선택
        function selectGrade(grade) {
            currentGrade = grade;
            selectedSubject = null; // 선택 초기화
            
            // 버튼 활성화
            document.querySelectorAll('.grade-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 과목 표시
            showSubjects(grade);
            
            // 시험 유형 표시
            showExamTypes(grade);
        }
        
        // 시험 유형 표시
        function showExamTypes(grade) {
            const container = document.getElementById('exam-types');
            const types = examTypes[grade];
            
            // 중등수학, 고등수학은 시험 유형을 표시하지 않음
            if (types.length === 0) {
                container.innerHTML = '';
                return;
            }
            
            const gridCols = types.length === 1 ? '1fr' : `repeat(${Math.min(types.length, 4)}, 1fr)`;
            
            container.innerHTML = `
                <div class="main-cards" style="grid-template-columns: ${gridCols}; margin-bottom: 2rem;">
                    ${types.map(exam => `
                        <div class="main-card midterm-card" onclick="navigateToExam('${exam.type}')">
                            <div class="icon">${exam.icon}</div>
                            <h2 style="font-size: 1.5rem;">${exam.type}</h2>
                            <p>${exam.desc}</p>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        // 과목 표시
        function showSubjects(grade) {
            const container = document.getElementById('subject-grid');
            const data = gradeSubjects[grade];
            
            container.innerHTML = data.subjects.map((subject, index) => `
                <div class="subject-card" id="subject-${index}" onclick="selectSubject('${grade}', '${subject.code}', '${subject.name}', ${index})">
                    <h3>${subject.name}</h3>
                    <p>${subject.desc}</p>
                </div>
            `).join('');
        }
        
        let selectedSubject = null;
        
        // 과목 선택
        function selectSubject(grade, code, name, index) {
            // 이전 선택 해제
            if (selectedSubject !== null) {
                document.getElementById(`subject-${selectedSubject}`).classList.remove('selected');
            }
            
            // 새 선택 적용
            selectedSubject = index;
            document.getElementById(`subject-${index}`).classList.add('selected');
            
            // 선택 정보 저장
            console.log('Saving subject selection for index3:', {
                pageType: pageType,
                subjectName: name,
                subjectCode: code,
                grade: grade
            });
            
            saveUserSelection(pageType, name, code, {
                grade: grade,
                subjectCode: code,
                subjectName: name,
                path: 'subject_selection'
            });
            
            // 초등수학인 경우 개념학습 링크로 이동
            if (grade === 'elementary') {
                const cidMap = {
                    '4-1': 73,
                    '4-2': 74,
                    '5-1': 75,
                    '5-2': 76,
                    '6-1': 78,
                    '6-2': 79
                };
                if (cidMap[code]) {
                    window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?&cid=${cidMap[code]}&type=init`;
                }
            }
            // 중등수학인 경우 미션홈 링크로 이동
            else if (grade === 'middle') {
                const cidMap = {
                    '1-1': 42,
                    '1-2': 43,
                    '2-1': 44,
                    '2-2': 45,
                    '3-1': 46,
                    '3-2': 47
                };
                if (cidMap[code]) {
                    window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=3&cid=${cidMap[code]}&tb=90`;
                }
            }
            // 고등수학인 경우 미션홈 링크로 이동
            else if (grade === 'high') {
                const cidMap = {
                    'common1': 2,
                    'common2': 36,
                    'algebra': 37,
                    'calculus1': 38,
                    'statistics': 40,
                    'calculus2': 39,
                    'geometry': 41
                };
                if (cidMap[code]) {
                    window.location.href = `https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=3&cid=${cidMap[code]}&tb=90`;
                }
            }
            
            // 초등수학의 단원평가는 기존대로 처리
            if (grade === 'elementary') {
                // 시험 유형 영역으로 스크롤
                setTimeout(() => {
                    document.getElementById('exam-types').scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 300);
            }
        }
        
        // 내신 준비 시작
        function startExamPrep(grade, code, name) {
            if (selectedSubject === null) {
                alert('먼저 과목을 선택해주세요!');
                return;
            }
            alert(`${name} 내신준비를 시작합니다!`);
            // 여기에서 실제 내신 콘텐츠로 이동
        }
        
        // 미니맵 토글
        function toggleMinimap() {
            const dropdown = document.getElementById('minimapDropdown');
            dropdown.classList.toggle('active');
        }
        
        // 미니맵 외부 클릭 시 닫기
        document.addEventListener('click', function(event) {
            const minimap = document.getElementById('minimapDropdown');
            const button = event.target.closest('[onclick="toggleMinimap()"]');
            
            if (!button && !minimap.contains(event.target)) {
                minimap.classList.remove('active');
            }
        });
        
        // 클릭 외부 영역 감지
        document.addEventListener('click', function(event) {
            const minimap = document.getElementById('minimapDropdown');
            const button = document.querySelector('.minimap-button');
            
            if (!minimap.contains(event.target) && !button.contains(event.target)) {
                minimap.classList.remove('active');
            }
        });
        
        // 섹션 전환
        function showSection(sectionNum) {
            // 모든 섹션 숨기기
            for (let i = 1; i <= 7; i++) {
                document.getElementById(`section-${i}`).style.display = 'none';
            }
            
            // 선택된 섹션 표시
            document.getElementById(`section-${sectionNum}`).style.display = 'block';
            currentSection = sectionNum;
        }
        
        // 다음 섹션
        function nextSection(nextNum) {
            // 현재 섹션 데이터 저장
            saveCurrentSection();
            
            // 다음 섹션으로 이동
            showSection(nextNum);
        }
        
        // 이전 섹션
        function prevSection(prevNum) {
            // 현재 섹션 데이터 저장
            saveCurrentSection();
            
            // 이전 섹션으로 이동
            showSection(prevNum);
        }
        
        // 현재 섹션 데이터 저장
        function saveCurrentSection() {
            switch(currentSection) {
                case 1:
                    examInfo.subject = document.getElementById('subject').value;
                    examInfo.examRange = document.getElementById('exam-range').value;
                    break;
                case 2:
                    examInfo.examDatetime = document.getElementById('exam-datetime').value;
                    break;
                case 3:
                    examInfo.arrivalTime = document.getElementById('arrival-time').value;
                    examInfo.studyTime = document.getElementById('study-time').value;
                    break;
                case 4:
                    examInfo.bookTitles = document.getElementById('book-titles').value;
                    examInfo.onlineContent = document.getElementById('online-content').value;
                    examInfo.schoolPrints = document.getElementById('school-prints').value;
                    break;
                case 5:
                    examInfo.teacherComments = document.getElementById('teacher-comments').value;
                    break;
                case 6:
                    examInfo.mainTypes = document.getElementById('main-types').value;
                    examInfo.recentTrends = document.getElementById('recent-trends').value;
                    break;
                case 7:
                    examInfo.minScore = document.getElementById('min-score').value;
                    examInfo.maxScore = document.getElementById('max-score').value;
                    break;
            }
        }
        
        // 시험 정보 저장
        function saveExamInfo() {
            // 마지막 섹션 데이터 저장
            saveCurrentSection();
            
            // 로컬 스토리지에 저장
            const examKey = `exam_${currentExamType}_${Date.now()}`;
            localStorage.setItem(examKey, JSON.stringify(examInfo));
            
            // 저장 완료 메시지
            alert(`${currentExamType} 시험 정보가 저장되었습니다!\n\n` +
                  `과목: ${examInfo.subject}\n` +
                  `시험 날짜: ${new Date(examInfo.examDatetime).toLocaleString('ko-KR')}\n` +
                  `목표 점수: ${examInfo.minScore}점 ~ ${examInfo.maxScore}점`);
            
            // 메인 화면으로 돌아가기
            navigateToLevel(0);
            
            // 폼 초기화
            resetExamInfo();
        }
        
        // 시험 정보 초기화
        function resetExamInfo() {
            examInfo = {
                subject: '',
                examRange: '',
                examDatetime: '',
                arrivalTime: '',
                studyTime: '',
                bookTitles: '',
                onlineContent: '',
                schoolPrints: '',
                teacherComments: '',
                mainTypes: '',
                recentTrends: '',
                minScore: '',
                maxScore: ''
            };
            
            // 폼 필드 초기화
            document.getElementById('subject').value = '';
            document.getElementById('exam-range').value = '';
            document.getElementById('exam-datetime').value = '';
            document.getElementById('arrival-time').value = '';
            document.getElementById('study-time').value = '';
            document.getElementById('book-titles').value = '';
            document.getElementById('online-content').value = '';
            document.getElementById('school-prints').value = '';
            document.getElementById('teacher-comments').value = '';
            document.getElementById('main-types').value = '';
            document.getElementById('recent-trends').value = '';
            document.getElementById('min-score').value = '';
            document.getElementById('max-score').value = '';
        }
        
        // 이미지 뷰어 관련 함수들
        let currentViewMode = 'tab'; // 'tab' or 'scroll'
        let currentTheme = 'dark'; // 'dark' or 'light'
        let currentProblemIndex = 0;
        
        // 샘플 문제 이미지 데이터 (실제로는 서버에서 가져올 데이터)
        const problemImages = [
            { id: 1, title: '문제 1', url: 'https://via.placeholder.com/800x600/3b82f6/ffffff?text=Problem+1' },
            { id: 2, title: '문제 2', url: 'https://via.placeholder.com/800x600/8b5cf6/ffffff?text=Problem+2' },
            { id: 3, title: '문제 3', url: 'https://via.placeholder.com/800x600/10b981/ffffff?text=Problem+3' },
            { id: 4, title: '문제 4', url: 'https://via.placeholder.com/800x600/f59e0b/ffffff?text=Problem+4' },
            { id: 5, title: '문제 5', url: 'https://via.placeholder.com/800x600/ef4444/ffffff?text=Problem+5' }
        ];
        
        // 이미지 뷰어 열기
        function openImageViewer() {
            const modal = document.getElementById('imageViewerModal');
            modal.classList.add('active');
            if (currentViewMode === 'tab') {
                showTabMode();
            } else {
                showScrollMode();
            }
        }
        
        // 이미지 뷰어 닫기
        function closeImageViewer() {
            const modal = document.getElementById('imageViewerModal');
            modal.classList.remove('active');
        }
        
        // 뷰 모드 전환
        function toggleViewMode() {
            currentViewMode = currentViewMode === 'tab' ? 'scroll' : 'tab';
            const tabBtn = document.getElementById('tabModeBtn');
            const scrollBtn = document.getElementById('scrollModeBtn');
            
            if (currentViewMode === 'tab') {
                tabBtn.classList.add('active');
                scrollBtn.classList.remove('active');
                showTabMode();
            } else {
                scrollBtn.classList.add('active');
                tabBtn.classList.remove('active');
                showScrollMode();
            }
        }
        
        // 탭 모드 표시
        function showTabMode() {
            document.getElementById('tabModeContainer').classList.add('active');
            document.getElementById('scrollModeContainer').classList.remove('active');
            
            // 탭 생성
            const tabsContainer = document.getElementById('problemTabs');
            const contentsContainer = document.getElementById('tabContents');
            tabsContainer.innerHTML = '';
            contentsContainer.innerHTML = '';
            
            problemImages.forEach((problem, index) => {
                // 탭 버튼 생성
                const tab = document.createElement('button');
                tab.className = 'problem-tab' + (index === 0 ? ' active' : '');
                tab.textContent = problem.title;
                tab.onclick = () => showProblemTab(index);
                tabsContainer.appendChild(tab);
                
                // 탭 콘텐츠 생성
                const content = document.createElement('div');
                content.className = 'tab-content' + (index === 0 ? ' active' : '');
                content.id = `tabContent${index}`;
                content.innerHTML = `
                    <div class="problem-item">
                        <span class="problem-number">${problem.id}</span>
                        <img src="${problem.url}" class="problem-image" onclick="toggleFullscreen(this)" alt="${problem.title}">
                    </div>
                `;
                contentsContainer.appendChild(content);
            });
        }
        
        // 스크롤 모드 표시
        function showScrollMode() {
            document.getElementById('scrollModeContainer').classList.add('active');
            document.getElementById('tabModeContainer').classList.remove('active');
            
            const container = document.getElementById('scrollModeContainer');
            container.innerHTML = '';
            
            problemImages.forEach((problem) => {
                const item = document.createElement('div');
                item.className = 'problem-item';
                item.innerHTML = `
                    <span class="problem-number">${problem.id}</span>
                    <img src="${problem.url}" class="problem-image" onclick="toggleFullscreen(this)" alt="${problem.title}">
                `;
                container.appendChild(item);
            });
        }
        
        // 특정 문제 탭 표시
        function showProblemTab(index) {
            currentProblemIndex = index;
            
            // 모든 탭과 콘텐츠 비활성화
            document.querySelectorAll('.problem-tab').forEach((tab, i) => {
                if (i === index) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
            
            document.querySelectorAll('.tab-content').forEach((content, i) => {
                if (i === index) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        }
        
        // 테마 전환
        function toggleTheme() {
            currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
            const modal = document.getElementById('imageViewerModal');
            const darkBtn = document.getElementById('darkModeBtn');
            const lightBtn = document.getElementById('lightModeBtn');
            
            if (currentTheme === 'light') {
                modal.classList.add('light-mode');
                lightBtn.classList.add('active');
                darkBtn.classList.remove('active');
            } else {
                modal.classList.remove('light-mode');
                darkBtn.classList.add('active');
                lightBtn.classList.remove('active');
            }
        }
        
        // 전체화면 토글
        function toggleFullscreen(img) {
            const overlay = document.getElementById('fullscreenOverlay');
            const fullscreenImg = document.getElementById('fullscreenImage');
            
            fullscreenImg.src = img.src;
            overlay.classList.add('active');
        }
        
        // 전체화면 닫기
        function closeFullscreen() {
            const overlay = document.getElementById('fullscreenOverlay');
            overlay.classList.remove('active');
        }
        
        // ESC 키로 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFullscreen();
                closeImageViewer();
            }
        });
    </script>
    
    <!-- 이미지 뷰어 모달 -->
    <div id="imageViewerModal" class="image-viewer-modal">
        <div class="viewer-header">
            <div class="viewer-controls">
                <button id="tabModeBtn" class="viewer-btn active" onclick="toggleViewMode()">📑 탭 모드</button>
                <button id="scrollModeBtn" class="viewer-btn" onclick="toggleViewMode()">📜 스크롤 모드</button>
                <span style="color: white; margin: 0 1rem;">|</span>
                <button id="darkModeBtn" class="viewer-btn active" onclick="toggleTheme()">🌙 다크 모드</button>
                <button id="lightModeBtn" class="viewer-btn" onclick="toggleTheme()">☀️ 라이트 모드</button>
            </div>
            <button class="viewer-btn" onclick="closeImageViewer()">✕ 닫기</button>
        </div>
        
        <div class="viewer-content">
            <!-- 탭 모드 컨테이너 -->
            <div id="tabModeContainer" class="tab-mode-container active">
                <div id="problemTabs" class="problem-tabs"></div>
                <div id="tabContents"></div>
            </div>
            
            <!-- 스크롤 모드 컨테이너 -->
            <div id="scrollModeContainer" class="scroll-mode-container"></div>
        </div>
    </div>
    
    <!-- 전체화면 오버레이 -->
    <div id="fullscreenOverlay" class="fullscreen-overlay" onclick="closeFullscreen()">
        <img id="fullscreenImage" class="fullscreen-image" src="" alt="전체화면 이미지">
        <button class="close-fullscreen" onclick="closeFullscreen()">✕</button>
    </div>
</body>
</html>