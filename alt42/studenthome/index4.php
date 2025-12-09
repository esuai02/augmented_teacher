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
    <title>수능대비 - Math Learning Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #1e293b 100%);
            min-height: 100vh;
            overflow-x: hidden;
            color: #333;
            display: flex;
            margin: 0;
        }

        .main-content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        /* 네비게이션 바 */
        .navbar {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-button {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .nav-button:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .timer-display {
            font-family: monospace;
            color: white;
            font-size: 1.1rem;
        }

        /* 메인 컨테이너 */
        .main-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            transition: all 0.8s ease-in-out;
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
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            text-align: center;
        }

        .welcome-subtitle {
            color: #cbd5e1;
            font-size: 1.25rem;
            margin-bottom: 3rem;
            text-align: center;
        }

        .main-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .main-card {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            padding: 3rem;
            border-radius: 1.5rem;
            text-align: center;
            color: white;
            cursor: pointer;
            transition: all 0.5s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .main-card:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }

        .main-card .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .main-card h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        /* 개념 카드 */
        .concept-card {
            --gradient-start: #3b82f6;
            --gradient-end: #2563eb;
        }

        /* 유형 카드 */
        .type-card {
            --gradient-start: #10b981;
            --gradient-end: #059669;
        }

        /* 기출 카드 */
        .past-card {
            --gradient-start: #8b5cf6;
            --gradient-end: #7c3aed;
        }

        /* 옴니모드 버튼 */
        .omni-button {
            width: 100%;
            max-width: 965px;
            padding: 1.5rem;
            background: linear-gradient(to right, 
                transparent 0%, 
                rgba(236, 72, 153, 0.3) 5%, 
                rgba(236, 72, 153, 1) 15%, 
                rgba(239, 68, 68, 1) 50%, 
                rgba(245, 158, 11, 1) 85%, 
                rgba(245, 158, 11, 0.3) 95%, 
                transparent 100%);
            border: none;
            border-radius: 1rem;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.5s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .omni-button:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }

        /* 레벨 1 - 과목 선택 */
        .level-1 {
            display: none;
        }

        .subject-title {
            text-align: center;
            color: white;
            font-size: 3rem;
            margin-bottom: 3rem;
        }

        .subject-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .subject-card {
            background: linear-gradient(135deg, var(--subject-gradient-start), var(--subject-gradient-end));
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .subject-card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        .subject-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* 과목별 색상 */
        .math1 {
            --subject-gradient-start: #ef4444;
            --subject-gradient-end: #dc2626;
        }

        .math2 {
            --subject-gradient-start: #f97316;
            --subject-gradient-end: #ea580c;
        }

        .calculus {
            --subject-gradient-start: #eab308;
            --subject-gradient-end: #ca8a04;
        }

        .probability {
            --subject-gradient-start: #10b981;
            --subject-gradient-end: #059669;
        }

        .geometry {
            --subject-gradient-start: #6366f1;
            --subject-gradient-end: #4f46e5;
        }

        /* 레벨 2 - 옴니모드 */
        .level-2 {
            display: none;
            height: calc(100vh - 80px);
            background: white;
        }

        .omni-header {
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            padding: 1rem 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .omni-content {
            display: flex;
            height: calc(100% - 140px);
        }

        /* 화이트보드 영역 */
        .whiteboard-section {
            flex: 1;
            padding: 2rem;
        }

        .whiteboard-container {
            background: white;
            border: 4px solid #e5e7eb;
            border-radius: 1rem;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .whiteboard-header {
            background: #f3f4f6;
            padding: 1rem;
            border-bottom: 2px solid #e5e7eb;
            border-radius: 0.75rem 0.75rem 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .whiteboard-tools {
            display: flex;
            gap: 0.5rem;
        }

        .tool-button {
            padding: 0.5rem;
            background: #e5e7eb;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tool-button.active {
            background: #3b82f6;
            color: white;
        }

        .tool-button:hover {
            background: #d1d5db;
        }

        .problem-display {
            padding: 1.5rem;
        }

        .problem-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 0 0.5rem 0.5rem 0;
            margin-bottom: 1rem;
        }

        .difficulty-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .difficulty-easy {
            background: #d1fae5;
            color: #065f46;
        }

        .canvas-wrapper {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        #whiteboard-canvas {
            width: 100%;
            height: 400px;
            background: white;
            cursor: crosshair;
        }

        .answer-input-group {
            display: flex;
            gap: 1rem;
        }

        .answer-input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .submit-button {
            padding: 0.5rem 2rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-button:hover {
            background: #059669;
        }

        /* AI 선생님 영역 */
        .ai-teacher-section {
            width: 320px;
            background: #f3f4f6;
            border-left: 4px solid #3b82f6;
            display: flex;
            flex-direction: column;
        }

        .ai-avatar-container {
            padding: 1rem;
            background: white;
            border-bottom: 2px solid #e5e7eb;
        }

        .ai-avatar {
            width: 100%;
            height: 200px;
            background: linear-gradient(to bottom, #60a5fa, #3b82f6);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .ai-face {
            width: 128px;
            height: 128px;
            background: #fef3c7;
            border-radius: 50%;
            border: 4px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
        }

        .chat-message {
            margin-bottom: 1rem;
            display: flex;
        }

        .chat-message.user {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 80%;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .message-bubble.ai {
            background: white;
            border: 1px solid #e5e7eb;
        }

        .message-bubble.user {
            background: #3b82f6;
            color: white;
        }

        .chat-input-container {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .chat-input-group {
            display: flex;
            gap: 0.5rem;
        }

        .chat-input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .chat-send-button {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
        }

        /* 하단 컨트롤 바 */
        .control-bar {
            background: #f3f4f6;
            padding: 1rem 2rem;
            border-top: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .control-buttons {
            display: flex;
            gap: 1rem;
        }

        .control-button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .next-button {
            background: #eab308;
            color: white;
        }

        .solution-button {
            background: #6b7280;
            color: white;
        }

        .reanalyze-button {
            background: #8b5cf6;
            color: white;
        }

        /* 미니맵 */
        .minimap {
            position: fixed;
            top: 100px;
            right: 20px;
            background: rgba(30, 41, 59, 0.95);
            border: 2px solid #475569;
            border-radius: 0.5rem;
            padding: 1rem;
            width: 250px;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            display: none;
        }

        .minimap h3 {
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .path-item {
            padding: 0.5rem;
            margin-bottom: 0.25rem;
            border-radius: 0.25rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .path-item:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .path-item.active {
            background: rgba(59, 130, 246, 0.4);
        }

        /* 애니메이션 */
        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .zoom-in {
            animation: zoomIn 0.8s ease-in-out;
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0) translateZ(-100px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateZ(0);
            }
        }

        /* 휴식 화면 */
        .break-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #1e293b;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .break-content {
            text-align: center;
            color: white;
        }

        .break-icon {
            font-size: 6rem;
            margin-bottom: 2rem;
        }

        .break-timer {
            font-size: 4rem;
            font-family: monospace;
            color: #60a5fa;
            margin-bottom: 2rem;
        }

        .end-break-button {
            padding: 0.75rem 1.5rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
        }
        
        /* 미니맵 */
        .minimap-button {
            padding: 0.5rem 1rem;
            background: #3b82f6;
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
            background: #2563eb;
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
            background: #3b82f6;
            color: white;
            font-weight: bold;
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
        // 공통 헤더 포함
        $active_page = 'index4';
        include_once('includes/header.php');
        ?>

        <!-- 메인 컨테이너 -->
    <div class="main-container" id="main-container">
        <!-- 레벨 0: 메인 대시보드 -->
        <div class="level-0" id="level-0">
            <h1 class="welcome-title">Welcome to Omni-Math</h1>
            <p class="welcome-subtitle">개념부터 실전까지 체계적인 수능 수학 학습</p>
            
            <div class="main-cards">
                <div class="main-card concept-card" onclick="navigateToSubjects('개념')">
                    <div class="icon">📚</div>
                    <h2>개념</h2>
                    <p>개념 학습</p>
                </div>
                
                <div class="main-card type-card" onclick="navigateToSubjects('유형')">
                    <div class="icon">🎯</div>
                    <h2>유형</h2>
                    <p>유형별 학습</p>
                </div>
                
                <div class="main-card past-card" onclick="navigateToSubjects('기출')">
                    <div class="icon">📝</div>
                    <h2>기출</h2>
                    <p>기출 문제</p>
                </div>
            </div>
            
            <button class="omni-button" onclick="navigateToOmniMode()">
                <div style="display: flex; align-items: center; justify-content: center; gap: 1rem;">
                    <span>🧠</span>
                    <span>🤖 옴니모드로 학습</span>
                    <span>⚡</span>
                </div>
                <p style="font-size: 1rem; font-weight: normal; margin-top: 0.5rem;">
                    AI가 진단하여 맞춤형 문제를 제공하는 스마트 학습
                </p>
            </button>
        </div>

        <!-- 레벨 1: 과목 선택 -->
        <div class="level-1" id="level-1">
            <h1 class="subject-title" id="category-title">개념</h1>
            <p style="text-align: center; color: #cbd5e1; font-size: 1.25rem; margin-bottom: 3rem;">
                과목을 선택하세요
            </p>
            
            <div class="subject-cards">
                <div class="subject-card math1" onclick="openSubjectLink('수학I')">
                    <div class="icon">🔢</div>
                    <h3>수학I</h3>
                    <span style="font-size: 0.875rem;">↗️</span>
                </div>
                
                <div class="subject-card math2" onclick="openSubjectLink('수학II')">
                    <div class="icon">📐</div>
                    <h3>수학II</h3>
                    <span style="font-size: 0.875rem;">↗️</span>
                </div>
                
                <div class="subject-card calculus" onclick="openSubjectLink('미적분')">
                    <div class="icon">∫</div>
                    <h3>미적분</h3>
                    <span style="font-size: 0.875rem;">↗️</span>
                </div>
                
                <div class="subject-card probability" onclick="openSubjectLink('확률과통계')">
                    <div class="icon">📊</div>
                    <h3>확률과통계</h3>
                    <span style="font-size: 0.875rem;">↗️</span>
                </div>
                
                <div class="subject-card geometry" onclick="openSubjectLink('기하')">
                    <div class="icon">📏</div>
                    <h3>기하</h3>
                    <span style="font-size: 0.875rem;">↗️</span>
                </div>
            </div>
            
            <p style="text-align: center; color: #94a3b8; font-size: 0.875rem; margin-top: 2rem;">
                과목을 클릭하면 학습 페이지가 열립니다
            </p>
        </div>

        <!-- 레벨 2: 옴니모드 -->
        <div class="level-2" id="level-2">
            <div class="omni-header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="font-size: 2rem;">🧠</span>
                    <div>
                        <h1 style="font-size: 1.5rem;">🤖 AI 옴니모드 학습</h1>
                        <p style="font-size: 0.875rem; opacity: 0.9;">실시간 맞춤형 학습 진행 중</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 2rem;">
                    <div style="text-align: right;">
                        <p style="font-size: 0.875rem;">현재 문제</p>
                        <p style="font-weight: bold;">수학I - 개념</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 0.5rem;">
                        <span style="font-family: monospace; font-size: 1.125rem;" id="omni-timer">0:00</span>
                    </div>
                </div>
            </div>

            <div class="omni-content">
                <!-- 화이트보드 영역 -->
                <div class="whiteboard-section">
                    <div class="whiteboard-container">
                        <div class="whiteboard-header">
                            <h2 style="font-size: 1.25rem; color: #374151;">문제 해결 공간</h2>
                            
                            <div class="whiteboard-tools">
                                <button class="tool-button active" id="pen-tool" onclick="selectTool('pen')">
                                    ✏️
                                </button>
                                <button class="tool-button" id="eraser-tool" onclick="selectTool('eraser')">
                                    🧽
                                </button>
                                <button class="tool-button" onclick="clearCanvas()" style="background: #ef4444; color: white;">
                                    전체 지우기
                                </button>
                            </div>
                        </div>
                        
                        <div class="problem-display">
                            <div class="problem-box">
                                <span class="difficulty-badge difficulty-easy">기초</span>
                                <span style="font-size: 0.875rem; color: #6b7280; margin-left: 0.5rem;">수학I - 개념</span>
                                <p style="font-size: 1.125rem; margin-top: 0.5rem; color: #1f2937;">
                                    함수 f(x) = 2x + 3에서 f(5)의 값을 구하시오.
                                </p>
                            </div>
                            
                            <div class="canvas-wrapper">
                                <canvas id="whiteboard-canvas"></canvas>
                            </div>
                            
                            <div class="answer-input-group">
                                <input type="text" class="answer-input" placeholder="답을 입력하세요...">
                                <button class="submit-button">제출</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI 선생님 영역 -->
                <div class="ai-teacher-section">
                    <div class="ai-avatar-container">
                        <div class="ai-avatar">
                            <div class="ai-face" id="ai-face">👨‍🏫</div>
                        </div>
                    </div>

                    <div class="chat-area">
                        <div class="chat-messages" id="chat-messages">
                            <div class="chat-message ai">
                                <div class="message-bubble ai">
                                    안녕하세요! AI 선생님입니다. 지금부터 맞춤형 학습을 시작하겠습니다. 먼저 현재 실력을 진단해보겠습니다.
                                </div>
                            </div>
                            <div class="chat-message ai">
                                <div class="message-bubble ai">
                                    첫 번째 문제를 준비했습니다. 천천히 풀어보세요!
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-input-container">
                            <div class="chat-input-group">
                                <input type="text" class="chat-input" id="chat-input" placeholder="질문을 입력하세요...">
                                <button class="chat-send-button" onclick="sendMessage()">전송</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="control-bar">
                <div class="control-buttons">
                    <button class="control-button next-button">⏭️ 다음 문제</button>
                    <button class="control-button solution-button">📝 풀이 보기</button>
                    <button class="control-button reanalyze-button">🔄 문제 재분석</button>
                </div>
                <div style="display: flex; gap: 2rem; font-size: 0.875rem; color: #6b7280;">
                    <div>AI 정확도: <span style="color: #10b981; font-weight: bold;">94%</span></div>
                    <div>해결 시간: <span style="font-family: monospace;">3:42</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 미니맵 -->
    <div class="minimap" id="minimap">
        <h3>학습 경로</h3>
        <div id="minimap-content">
            <div class="path-item active" onclick="goHome()">
                🏠 수능 수학 홈
            </div>
        </div>
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #475569;">
            <div style="font-size: 0.875rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>학습 시간</span>
                    <span style="font-family: monospace;" id="minimap-timer">0:00</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>뽀모도로</span>
                    <span id="pomodoro-count">0회</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 휴식 화면 -->
    <div class="break-screen" id="break-screen">
        <div class="break-content">
            <div class="break-icon">☕</div>
            <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">휴식 시간</h2>
            <p style="font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.8;">
                잠시 눈을 쉬게 하고 스트레칭을 해보세요
            </p>
            <div class="break-timer" id="break-timer">5:00</div>
            <button class="end-break-button" onclick="endBreak()">휴식 종료</button>
        </div>
    </div>

    <script src="js/save_selection.js"></script>
    <script>
        // 전역 상태 관리
        let currentLevel = 0;
        let currentPath = [];
        let currentCategory = '';
        
        // 사용자 정보
        const studentId = <?php echo $studentid; ?>;
        const pageType = 'index4';
        let studyTime = 0;
        let breakTime = 0;
        let isOnBreak = false;
        let pomodoroCount = 0;
        let focusScore = 100;
        let isDrawing = false;
        let currentTool = 'pen';
        let canvas, ctx;

        // URL 매핑
        const urlMapping = {
            "개념": {
                "수학I": "https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=61&nch=1&type=init",
                "수학II": "https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=62&nch=1&type=init",
                "미적분": "https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?&cid=64&nch=1&type=init",
                "확률과통계": "https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?&cid=63&nch=1&type=init",
                "기하": "https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?cid=65&nch=1&type=init"
            },
            "유형": {
                "수학I": "https://mathking.kr/moodle/mod/checklist/index.php?id=301",
                "수학II": "https://mathking.kr/moodle/mod/checklist/index.php?id=302",
                "미적분": "https://mathking.kr/moodle/mod/checklist/index.php?id=303",
                "확률과통계": "https://mathking.kr/moodle/mod/checklist/index.php?id=304",
                "기하": "https://mathking.kr/moodle/mod/checklist/index.php?id=328"
            },
            "기출": {
                "수학I": "https://mathking.kr/moodle/mod/checklist/view.php?id=92613",
                "수학II": "https://mathking.kr/moodle/mod/checklist/view.php?id=92616",
                "미적분": "https://mathking.kr/moodle/mod/checklist/view.php?id=92614",
                "확률과통계": "https://mathking.kr/moodle/mod/checklist/view.php?id=92620",
                "기하": "https://mathking.kr/moodle/mod/checklist/view.php?id=92622"
            }
        };

        // 초기화
        window.onload = function() {
            // 캔버스 초기화
            canvas = document.getElementById('whiteboard-canvas');
            ctx = canvas.getContext('2d');
            setupCanvas();
            
            // 타이머 시작
            startTimer();
            
            // 채팅 엔터키 이벤트
            document.getElementById('chat-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
            
            // 마지막 선택 복원
            <?php if ($should_restore && $last_selection): ?>
            const lastData = <?php echo json_encode(json_decode($last_selection->selection_data, true)); ?>;
            if (lastData && lastData.category && lastData.subject) {
                // 카테고리로 이동
                navigateToSubjects(lastData.category);
                
                <?php if ($direct_to_study): ?>
                // 직접 학습 페이지로 이동
                setTimeout(() => {
                    const subjectCards = document.querySelectorAll('.subject-card');
                    subjectCards.forEach(card => {
                        if (card.textContent.includes(lastData.subject)) {
                            card.click();
                        }
                    });
                }, 800);
                <?php else: ?>
                // 과목 하이라이트
                setTimeout(() => {
                    const subjectCards = document.querySelectorAll('.subject-card');
                    subjectCards.forEach(card => {
                        if (card.textContent.includes(lastData.subject)) {
                            card.style.border = '2px solid #3b82f6';
                            card.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.4)';
                            
                            // 최근 라벨 추가
                            const label = document.createElement('div');
                            label.style.position = 'absolute';
                            label.style.top = '-10px';
                            label.style.right = '-10px';
                            label.style.background = '#3b82f6';
                            label.style.color = 'white';
                            label.style.padding = '2px 8px';
                            label.style.borderRadius = '12px';
                            label.style.fontSize = '0.75rem';
                            label.style.fontWeight = 'bold';
                            label.textContent = '최근';
                            card.style.position = 'relative';
                            card.appendChild(label);
                        }
                    });
                }, 500);
                <?php endif; ?>
            }
            <?php endif; ?>
        };

        // 캔버스 설정
        function setupCanvas() {
            if (!canvas) return;
            
            // 캔버스 크기 설정
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = 400;
            
            // 배경색 설정
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // 마우스 이벤트
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
        }

        // 타이머 관리
        function startTimer() {
            setInterval(() => {
                if (!isOnBreak) {
                    studyTime++;
                    updateTimerDisplay();
                    
                    // 집중도 감소
                    focusScore = Math.max(0, focusScore - 0.05);
                    document.getElementById('focus-score').textContent = Math.round(focusScore) + '%';
                    
                    // 25분마다 휴식
                    if (studyTime >= 1500) {
                        startBreak();
                    }
                } else {
                    breakTime++;
                    updateBreakTimer();
                    
                    // 집중도 회복
                    focusScore = Math.min(100, focusScore + 0.5);
                    
                    // 5분 후 휴식 종료
                    if (breakTime >= 300) {
                        endBreak();
                    }
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(studyTime / 60);
            const seconds = studyTime % 60;
            const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            document.getElementById('study-timer').textContent = timeString;
            document.getElementById('minimap-timer').textContent = timeString;
            
            if (currentLevel === 2) {
                document.getElementById('omni-timer').textContent = timeString;
            }
        }

        function updateBreakTimer() {
            const remainingTime = 300 - breakTime;
            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            document.getElementById('break-timer').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        function startBreak() {
            isOnBreak = true;
            studyTime = 0;
            breakTime = 0;
            pomodoroCount++;
            document.getElementById('pomodoro-count').textContent = pomodoroCount + '회';
            document.getElementById('break-screen').style.display = 'flex';
            
            // 알림
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('휴식 시간!', {
                    body: '5분간 휴식하세요. 물을 마시고 스트레칭을 해보세요.',
                    icon: '☕'
                });
            }
        }

        function endBreak() {
            isOnBreak = false;
            breakTime = 0;
            document.getElementById('break-screen').style.display = 'none';
            
            // 알림
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('학습 재개!', {
                    body: '다시 집중해볼까요?',
                    icon: '📚'
                });
            }
        }

        // 네비게이션
        function navigateToLevel(level, path) {
            // 이전 레벨 숨기기
            document.getElementById(`level-${currentLevel}`).style.display = 'none';
            
            // 새 레벨 표시
            currentLevel = level;
            currentPath = path;
            
            const newLevelElement = document.getElementById(`level-${level}`);
            newLevelElement.style.display = level === 2 ? 'flex' : 'block';
            newLevelElement.classList.add('fade-in');
            
            // 뒤로 버튼 표시/숨기기
            document.getElementById('back-button').style.display = level > 0 && path[0] !== 'omni' ? 'flex' : 'none';
            
            // 미니맵 업데이트
            updateMinimap();
            
            // 애니메이션 클래스 제거
            setTimeout(() => {
                newLevelElement.classList.remove('fade-in');
            }, 800);
        }

        function goHome() {
            navigateToLevel(0, []);
        }

        function goBack() {
            if (currentLevel > 0) {
                const newPath = [...currentPath];
                newPath.pop();
                navigateToLevel(currentLevel - 1, newPath);
            }
        }

        function navigateToSubjects(category) {
            currentCategory = category;
            document.getElementById('category-title').textContent = category;
            navigateToLevel(1, [category]);
        }

        function navigateToOmniMode() {
            navigateToLevel(2, ['omni']);
            
            // 옴니모드 진입 시 캔버스 재초기화
            setTimeout(() => {
                setupCanvas();
            }, 100);
        }

        function openSubjectLink(subject) {
            // 선택 정보 저장
            saveUserSelection(pageType, subject, currentCategory, {
                category: currentCategory,
                subject: subject,
                path: currentPath.join(' > ')
            });
            
            const url = urlMapping[currentCategory][subject];
            if (url) {
                window.location.href = url;
            }
        }


        function updateMinimap() {
            const content = document.getElementById('minimap-content');
            let html = '<div class="path-item" onclick="goHome()">🏠 수능 수학 홈</div>';
            
            if (currentPath.length > 0) {
                if (currentPath[0] === 'omni') {
                    html += '<div class="path-item active">🧠 🤖 AI 옴니모드</div>';
                } else {
                    html += `<div class="path-item active" style="margin-left: 1rem;">📚 ${currentPath[0]}</div>`;
                }
            }
            
            content.innerHTML = html;
        }

        // 화이트보드 기능
        function selectTool(tool) {
            currentTool = tool;
            document.querySelectorAll('.tool-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(`${tool}-tool`).classList.add('active');
        }

        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        function startDrawing(e) {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        function draw(e) {
            if (!isDrawing) return;
            
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.lineWidth = currentTool === 'eraser' ? 20 : 3;
            ctx.lineCap = 'round';
            
            if (currentTool === 'eraser') {
                ctx.globalCompositeOperation = 'destination-out';
            } else {
                ctx.globalCompositeOperation = 'source-over';
                ctx.strokeStyle = '#000000';
            }
            
            ctx.lineTo(x, y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        function stopDrawing() {
            isDrawing = false;
        }

        // 채팅 기능
        function sendMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            // 사용자 메시지 추가
            addChatMessage('user', message);
            input.value = '';
            
            // AI 응답 시뮬레이션
            setTimeout(() => {
                const aiResponse = getAIResponse(message);
                addChatMessage('ai', aiResponse);
                updateAIEmotion('explaining');
            }, 1000);
        }

        function addChatMessage(sender, message) {
            const chatMessages = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${sender}`;
            
            const bubble = document.createElement('div');
            bubble.className = `message-bubble ${sender}`;
            bubble.textContent = message;
            
            messageDiv.appendChild(bubble);
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function getAIResponse(message) {
            // 간단한 응답 시뮬레이션
            const responses = [
                "좋은 질문이네요! 단계별로 설명해드릴게요.",
                "이 부분은 중요한 개념입니다. 함께 살펴봅시다.",
                "잘 하고 있어요! 다음 단계로 넘어가볼까요?",
                "혹시 이 부분이 어려우신가요? 다른 방법으로 설명해드릴게요.",
                "맞습니다! 정확하게 이해하셨네요."
            ];
            
            return responses[Math.floor(Math.random() * responses.length)];
        }

        function updateAIEmotion(emotion) {
            const face = document.getElementById('ai-face');
            const emotions = {
                'normal': '👨‍🏫',
                'happy': '😊',
                'thinking': '🤔',
                'explaining': '🤓'
            };
            
            face.textContent = emotions[emotion] || emotions['normal'];
        }

        // 알림 권한 요청
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
    </script>
    </div> <!-- main-content-wrapper 닫기 -->
</body>
</html>