<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB,$USER;
require_login();

// GET 파라미터에서 userid 가져오기, 없으면 현재 로그인한 사용자 ID 사용
$userid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student'; // 기본값은 student
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>메타인지 홈</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 메인 컨테이너 */
        .main-container {
            display: flex;
            height: 100vh;
            background: white;
            overflow: hidden;
            position: relative;
        }

        /* 좌측 사이드바 */
        .sidebar {
            width: 280px;
            background: #2d3748;
            color: white;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            position: relative;
            z-index: 1000;
        }

        /* 모바일 메뉴 토글 버튼 */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 0.5rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .mobile-menu-toggle:hover {
            background: #5a67d8;
        }

        /* 모바일 오버레이 */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-header {
            padding: 1.5rem;
            background: #1a202c;
            border-bottom: 1px solid #4a5568;
        }

        .header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .header-buttons button {
            background: none;
            border: none;
            color: #718096;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.25rem;
            transition: all 0.3s;
        }

        .header-buttons button:hover {
            color: white;
        }
        
        /* 미니맵 */
        .minimap-button {
            background: none;
            border: none;
            color: #718096;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.25rem;
            transition: all 0.3s;
            position: relative;
        }
        
        .minimap-button:hover {
            color: white;
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
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            background: #667eea;
            color: white;
            font-weight: bold;
        }

        /* 검색 */
        .search-container {
            display: flex;
            align-items: center;
            background: #4a5568;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
        }

        .search-icon {
            margin-right: 0.5rem;
        }

        .search-input {
            background: none;
            border: none;
            color: white;
            outline: none;
            flex: 1;
            font-size: 0.875rem;
        }

        .search-input::placeholder {
            color: #a0aec0;
        }

        /* 메뉴 카테고리 */
        .menu-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
        }

        .menu-category {
            margin-bottom: 0.5rem;
        }

        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .category-header:hover {
            background: #4a5568;
        }

        .category-header.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .category-header.active::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #fbbf24;
        }

        .category-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
        }

        .category-icon {
            font-size: 1.5rem;
        }

        .category-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
        }

        .category-status.inactive {
            background: #6b7280;
            box-shadow: none;
        }

        /* 우측 콘텐츠 영역 */
        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f7fafc;
        }

        /* 콘텐츠 헤더 */
        .content-header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .current-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .section-info h2 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .section-info p {
            color: #718096;
            font-size: 0.875rem;
        }

        /* 모드 스위처 */
        .mode-switcher {
            display: flex;
            gap: 0.5rem;
            background: #edf2f7;
            padding: 0.25rem;
            border-radius: 0.5rem;
        }

        .mode-button {
            padding: 0.5rem 1rem;
            border: none;
            background: none;
            color: #718096;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: 0.375rem;
            transition: all 0.3s;
        }

        .mode-button.active {
            background: white;
            color: #667eea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* 서브카테고리 탭 */
        .subcategory-tabs {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: none;
        }

        .subcategory-tabs.active {
            display: block;
        }

        .tabs-container {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
        }

        .tab-item {
            padding: 0.5rem 1rem;
            background: #f7fafc;
            border-radius: 0.5rem;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4a5568;
        }

        .tab-item:hover {
            background: #e2e8f0;
        }

        .tab-item.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        /* 메타인지 대시보드 */
        .metacognition-dashboard {
            padding: 2rem;
            display: none;
            overflow-y: auto;
        }

        .metacognition-dashboard.active {
            display: block;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            transition: all 0.3s;
            cursor: pointer;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .card-content {
            color: #718096;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .progress-bar {
            margin-top: 1rem;
            background: #e2e8f0;
            border-radius: 0.5rem;
            height: 8px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: width 0.5s ease;
        }

        /* 메뉴 탭 */
        .menu-tab-container {
            padding: 2rem;
            display: none;
            overflow-y: auto;
        }

        .menu-tab-container.active {
            display: block;
        }

        .menu-tab-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }

        .menu-tab-item {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .menu-tab-item:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .menu-tab-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }

        .menu-tab-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .menu-tab-desc {
            font-size: 0.75rem;
            color: #718096;
        }

        /* 채팅 영역 - 슬라이드 패널 */
        .chat-panel {
            position: fixed;
            top: 0;
            right: -25%;
            width: 25%;
            height: 100vh;
            background: #ffffff;
            box-shadow: -4px 0 12px rgba(0, 0, 0, 0.15);
            transition: right 0.3s ease-in-out;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .chat-panel.active {
            right: 0;
        }
        .chat-panel-header {
            padding: 1rem;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-panel-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
        }
        .chat-panel-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .chat-panel-close:hover {
            background-color: #e5e7eb;
        }
        .chat-area {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #fafafa;
        }
        .chat-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .chat-panel-input {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }
        .chat-panel-input-wrapper {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .chat-panel-input input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .chat-panel-input input:focus {
            border-color: #3b82f6;
        }
        .chat-panel-input button {
            padding: 0.75rem 1rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }
        .chat-panel-input button:hover {
            background: #2563eb;
        }
        .chat-panel-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.3);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease-in-out;
            z-index: 999;
        }
        .chat-panel-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .chat-message {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.3s ease;
        }

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

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .message-name {
            font-weight: 600;
            color: #2d3748;
        }

        .message-time {
            font-size: 0.75rem;
            color: #a0aec0;
        }

        .message-text {
            color: #4a5568;
            line-height: 1.6;
        }

        /* 연쇄상호작용 알림 */
        .chain-notification {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* 기존 입력 영역 스타일 제거 - 새로운 채팅 패널 사용 */

        .send-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* 모바일 반응형 스타일 */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                width: 85%;
                max-width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .mobile-menu-toggle {
                display: block;
            }

            .mobile-overlay {
                display: none;
            }

            .mobile-overlay.active {
                display: block;
            }

            .content-header {
                padding-left: 4rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 1rem;
            }

            .menu-tab-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
                padding: 1rem;
            }

            .header-info {
                flex-direction: column;
                gap: 1rem;
            }

            .mode-switcher {
                width: 100%;
                justify-content: center;
            }

            .tabs-container {
                padding: 0 1rem;
                gap: 0.5rem;
            }

            .tab-item {
                padding: 0.4rem 0.8rem;
                font-size: 0.813rem;
            }

            
            /* 채팅 패널 모바일 스타일 */
            .chat-panel {
                width: 100%;
                right: -100%;
            }
            .chat-panel.active {
                right: 0;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                max-width: none;
            }

            .header-title h1 {
                font-size: 1.25rem;
            }

            .section-info h2 {
                font-size: 1.25rem;
            }

            .section-info p {
                font-size: 0.813rem;
            }

            .mode-button {
                padding: 0.4rem 0.8rem;
                font-size: 0.813rem;
            }

            .dashboard-card {
                padding: 1.25rem;
            }

            .card-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
            }

            .card-title {
                font-size: 1rem;
            }

            .card-desc {
                font-size: 0.813rem;
            }

            .card-stats {
                font-size: 0.75rem;
            }

            .menu-tab-card {
                padding: 1rem;
            }

            .menu-tab-title {
                font-size: 0.875rem;
            }

            .menu-tab-desc {
                font-size: 0.75rem;
            }

            .chat-message {
                padding: 0.875rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <!-- 모바일 메뉴 토글 버튼 -->
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <!-- 모바일 오버레이 -->
    <div class="mobile-overlay" onclick="closeMobileMenu()"></div>

    <div class="main-container">
        <!-- 좌측 사이드바 -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="header-title">
                    <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/index.php" style="text-decoration: none; color: inherit;">
                        <h1>🏠 메타인지</h1>
                    </a>
                    <div class="header-buttons">
                        <button><a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/wxsperta/wxsperta.php?userid=<?php echo $userid; ?>">🔔</a></button>
                        <button><a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/selectmode.php?userid=<?php echo $userid; ?>&student_id=827&role=teacher">⚙️</a></button>
                    </div>
                </div>
                <div class="search-container">
                    <div class="search-icon">🔍</div>
                    <input type="text" class="search-input" placeholder="메뉴 검색...">
                </div>
            </div>
            
            <div class="menu-list">
                <!-- 개념공부 -->
                <div class="menu-category" data-category="concept">
                    <div class="category-header" onclick="selectCategory('concept')">
                        <div class="category-title">
                            <span class="category-icon">📚</span>
                            <span>개념공부</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 문제풀이 -->
                <div class="menu-category" data-category="problem">
                    <div class="category-header" onclick="selectCategory('problem')">
                        <div class="category-title">
                            <span class="category-icon">✏️</span>
                            <span>문제풀이</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 학습관리 -->
                <div class="menu-category" data-category="learning">
                    <div class="category-header" onclick="selectCategory('learning')">
                        <div class="category-title">
                            <span class="category-icon">📊</span>
                            <span>학습관리</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 시험대비 -->
                <div class="menu-category" data-category="exam">
                    <div class="category-header" onclick="selectCategory('exam')">
                        <div class="category-title">
                            <span class="category-icon">📝</span>
                            <span>시험대비</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 실전연습 -->
                <div class="menu-category" data-category="practice">
                    <div class="category-header" onclick="selectCategory('practice')">
                        <div class="category-title">
                            <span class="category-icon">🎯</span>
                            <span>실전연습</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>

                <!-- 출결관리 -->
                <div class="menu-category" data-category="attendance">
                    <div class="category-header" onclick="selectCategory('attendance')">
                        <div class="category-title">
                            <span class="category-icon">📅</span>
                            <span>출결관리</span>
                        </div>
                        <span class="category-status"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 우측 콘텐츠 영역 -->
        <div class="content-area">
            <!-- 헤더 -->
            <div class="content-header">
                <div class="header-info">
                    <div class="current-section">
                        <div class="section-avatar" id="sectionAvatar">🧠</div>
                        <div class="section-info">
                            <h2 id="sectionTitle">메타인지 학습 시스템</h2>
                            <p id="sectionDesc">인지관성을 개선하고 효과적인 학습 환경을 만듭니다</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div class="mode-switcher">
                            <button class="mode-button active" onclick="switchMode('dashboard')">
                                💡 대시보드
                            </button>
                            <button class="mode-button" onclick="switchMode('menu')">
                                📋 메뉴
                            </button>
                            <button class="mode-button" onclick="switchMode('chat')">
                                💬 상담
                            </button>
                        </div>
                        <div style="position: relative;">
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
                                <a href="index3.php" class="minimap-item">
                                    <span>📝</span>
                                    <span>내신준비</span>
                                </a>
                                <a href="index4.php" class="minimap-item">
                                    <span>🎓</span>
                                    <span>수능대비</span>
                                </a>
                                <a href="indexm.php" class="minimap-item current">
                                    <span>🧠</span>
                                    <span>메타인지</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            

            <!-- 서브카테고리 탭 -->
            <div class="subcategory-tabs" id="subcategoryTabs">
                <div class="tabs-container" id="tabsContainer">
                    <!-- 탭이 동적으로 생성됩니다 -->
                </div>
            </div>

            <!-- 메타인지 대시보드 -->
            <div class="metacognition-dashboard active" id="dashboardMode">
                <div class="dashboard-grid" id="dashboardGrid">
                    <!-- 대시보드 카드들이 동적으로 생성됩니다 -->
                </div>
            </div>

            <!-- 메뉴 탭 -->
            <div class="menu-tab-container" id="menuMode">
                <div class="menu-tab-grid" id="menuTabGrid">
                    <!-- 메뉴 아이템들이 동적으로 생성됩니다 -->
                </div>
            </div>

        </div>
    </div>

    <script>
        // 전역 변수
        let currentCategory = 'concept';
        let currentSubcategory = null;
        let currentMode = 'dashboard';

        // 모바일 메뉴 토글
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
        
        // 모바일 메뉴 닫기
        function closeMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }

        // 카테고리별 데이터 (실제 teacherhome 구조 반영)
        const categoryData = {
            concept: {
                title: '개념공부',
                icon: '📚',
                desc: '체계적인 개념 학습과 이해도 향상',
                subcategories: {
                    'pomodoro': { name: '포모도르설정', icon: '⏰' },
                    'notes': { name: '개념노트 사용법', icon: '📓' },
                    'voice': { name: '음성대화 사용법', icon: '🎤' },
                    'test': { name: '테스트 응시방법', icon: '✍️' },
                    'qa': { name: '질의응답 및 지면평가', icon: '💬' }
                },
                dashboard: [
                    {
                        title: '오늘의 학습 목표',
                        icon: '🎯',
                        content: '수학 II - 미분 개념 완성하기',
                        progress: 65,
                        subcategory: 'notes'
                    },
                    {
                        title: '포모도르 세션',
                        icon: '⏰',
                        content: '오늘 4/6 세션 완료',
                        progress: 67,
                        subcategory: 'pomodoro'
                    },
                    {
                        title: '개념 이해도',
                        icon: '📊',
                        content: 'AI 평가: 78% 이해',
                        progress: 78,
                        subcategory: 'voice'
                    },
                    {
                        title: '다음 테스트',
                        icon: '✍️',
                        content: '미분법 단원 테스트 D-2',
                        progress: 85,
                        subcategory: 'test'
                    }
                ],
                menu: {
                    'pomodoro': [
                        { icon: '⏱️', title: '집중 타이머', desc: '25분 집중, 5분 휴식' },
                        { icon: '📊', title: '세션 통계', desc: '일일 집중도 분석' },
                        { icon: '🎯', title: '목표 설정', desc: '세션별 학습 목표' },
                        { icon: '🔔', title: '알림 설정', desc: '휴식 시간 알림' }
                    ],
                    'notes': [
                        { icon: '📝', title: '개념 정리', desc: '핵심 개념 요약' },
                        { icon: '🗂️', title: '노트 구조화', desc: '마인드맵 생성' },
                        { icon: '🔍', title: '개념 검색', desc: '빠른 개념 찾기' },
                        { icon: '📤', title: '노트 공유', desc: '학습 그룹 공유' }
                    ],
                    'voice': [
                        { icon: '🎙️', title: 'AI 음성 대화', desc: '개념 설명 요청' },
                        { icon: '🗣️', title: '발음 연습', desc: '수학 용어 발음' },
                        { icon: '🎧', title: '듣기 학습', desc: '개념 강의 듣기' },
                        { icon: '💬', title: '대화 기록', desc: '학습 대화 저장' }
                    ]
                }
            },
            problem: {
                title: '문제풀이',
                icon: '✏️',
                desc: '단계별 문제 해결 능력 향상',
                subcategories: {
                    'start': { name: '문제풀이 시작', icon: '🚀' },
                    'process': { name: '문제풀이 과정', icon: '🔄' },
                    'finish': { name: '문제풀이 마무리', icon: '✅' }
                },
                dashboard: [
                    {
                        title: '오늘 푼 문제',
                        icon: '📝',
                        content: '25문제 / 목표 30문제',
                        progress: 83,
                        subcategory: 'process'
                    },
                    {
                        title: '정답률',
                        icon: '✅',
                        content: '평균 정답률: 72%',
                        progress: 72,
                        subcategory: 'finish'
                    },
                    {
                        title: '풀이 시간',
                        icon: '⏱️',
                        content: '평균 3분 42초',
                        progress: 85,
                        subcategory: 'process'
                    },
                    {
                        title: '문제 분석',
                        icon: '🔍',
                        content: '취약 유형: 함수',
                        progress: 45,
                        subcategory: 'start'
                    }
                ],
                menu: {
                    'start': [
                        { icon: '📋', title: '문제 분석', desc: '문제 유형 파악' },
                        { icon: '🎯', title: '전략 수립', desc: '풀이 계획 세우기' },
                        { icon: '⏰', title: '시간 배분', desc: '효율적 시간 관리' },
                        { icon: '💡', title: '힌트 활용', desc: '단계별 도움말' }
                    ],
                    'process': [
                        { icon: '✍️', title: '단계별 풀이', desc: '체계적 접근법' },
                        { icon: '🔄', title: '과정 기록', desc: '풀이 과정 저장' },
                        { icon: '💬', title: 'AI 도움', desc: '실시간 피드백' },
                        { icon: '📊', title: '진행 상황', desc: '풀이 진도 체크' }
                    ],
                    'finish': [
                        { icon: '✅', title: '답안 검증', desc: '정답 확인하기' },
                        { icon: '📝', title: '오답 분석', desc: '실수 패턴 찾기' },
                        { icon: '💾', title: '풀이 저장', desc: '나만의 풀이법' },
                        { icon: '🔄', title: '복습 예약', desc: '재학습 스케줄' }
                    ]
                }
            },
            learning: {
                title: '학습관리',
                icon: '📊',
                desc: '체계적인 학습 계획과 실행',
                subcategories: {
                    'studyroom': { name: '내공부방', icon: '🏠' },
                    'results': { name: '공부결과', icon: '📈' },
                    'goals': { name: '목표설정', icon: '🎯' },
                    'diary': { name: '수학일기', icon: '📔' },
                    'quarterly': { name: '분기목표', icon: '📅' },
                    'schedule': { name: '시간표', icon: '⏰' }
                },
                dashboard: [
                    {
                        title: '주간 학습량',
                        icon: '📅',
                        content: '이번 주: 15시간 / 20시간',
                        progress: 75,
                        subcategory: 'schedule'
                    },
                    {
                        title: '목표 달성률',
                        icon: '🎯',
                        content: '이번 달: 88%',
                        progress: 88,
                        subcategory: 'goals'
                    },
                    {
                        title: '학습 일지',
                        icon: '📔',
                        content: '연속 작성: 12일',
                        progress: 100,
                        subcategory: 'diary'
                    },
                    {
                        title: '성과 분석',
                        icon: '📈',
                        content: '상승 추세 지속',
                        progress: 92,
                        subcategory: 'results'
                    }
                ],
                menu: {
                    'studyroom': [
                        { icon: '🏠', title: '나의 학습 공간', desc: '개인화된 환경' },
                        { icon: '📚', title: '학습 자료실', desc: '맞춤 콘텐츠' },
                        { icon: '🎨', title: '공간 꾸미기', desc: '동기부여 환경' },
                        { icon: '🏆', title: '성취 전시실', desc: '학습 성과 기록' }
                    ],
                    'results': [
                        { icon: '📊', title: '성과 대시보드', desc: '종합 성과 분석' },
                        { icon: '📈', title: '성장 그래프', desc: '학습 곡선 추적' },
                        { icon: '🎯', title: '목표 대비 실적', desc: '달성도 분석' },
                        { icon: '📋', title: '상세 리포트', desc: '심층 분석 보고서' }
                    ],
                    'goals': [
                        { icon: '🎯', title: 'SMART 목표', desc: '구체적 목표 설정' },
                        { icon: '📅', title: '장단기 계획', desc: '기간별 목표 관리' },
                        { icon: '✅', title: '체크리스트', desc: '일일 실행 항목' },
                        { icon: '🏆', title: '보상 시스템', desc: '목표 달성 보상' }
                    ]
                }
            },
            exam: {
                title: '시험대비',
                icon: '📝',
                desc: '체계적이고 전략적인 시험 준비',
                subcategories: {
                    'diagnosis': { name: '준비상태 진단', icon: '🔍' },
                    'period': { name: '기간별 전략', icon: '📅' },
                    'optimize': { name: '구간별 최적화', icon: '⚡' },
                    'practice': { name: '내신/기출 연습', icon: '📚' },
                    'memory': { name: '기억인출 전략', icon: '🧠' }
                },
                dashboard: [
                    {
                        title: 'D-Day',
                        icon: '📅',
                        content: '중간고사까지 D-14',
                        progress: 30,
                        subcategory: 'period'
                    },
                    {
                        title: '준비 상태',
                        icon: '🔍',
                        content: '진단 점수: 78점',
                        progress: 78,
                        subcategory: 'diagnosis'
                    },
                    {
                        title: '진도율',
                        icon: '📚',
                        content: '시험범위: 85% 완료',
                        progress: 85,
                        subcategory: 'practice'
                    },
                    {
                        title: '암기 상태',
                        icon: '🧠',
                        content: '공식 암기: 92%',
                        progress: 92,
                        subcategory: 'memory'
                    }
                ],
                menu: {
                    'diagnosis': [
                        { icon: '🔍', title: '실력 진단', desc: '현재 수준 파악' },
                        { icon: '📊', title: '취약점 분석', desc: '보완 필요 영역' },
                        { icon: '🎯', title: '목표 설정', desc: '현실적 목표 수립' },
                        { icon: '📋', title: '준비 체크리스트', desc: '필수 준비 사항' }
                    ],
                    'period': [
                        { icon: '📅', title: '4주 전략', desc: '장기 준비 계획' },
                        { icon: '📆', title: '2주 전략', desc: '집중 학습 기간' },
                        { icon: '🗓️', title: '1주 전략', desc: '최종 정리 기간' },
                        { icon: '⏰', title: 'D-Day 전략', desc: '시험 당일 계획' }
                    ],
                    'optimize': [
                        { icon: '⚡', title: '효율성 극대화', desc: '시간 대비 효과' },
                        { icon: '🎯', title: '핵심 집중', desc: '중요도별 학습' },
                        { icon: '🔄', title: '반복 최적화', desc: '효과적 복습' },
                        { icon: '💪', title: '컨디션 관리', desc: '최상의 상태 유지' }
                    ]
                }
            },
            practice: {
                title: '실전연습',
                icon: '🎯',
                desc: '실제 시험과 동일한 환경에서 연습',
                subcategories: {
                    'time': { name: '시간관리', icon: '⏰' },
                    'mistake': { name: '실수 조절하기', icon: '🎯' },
                    'order': { name: '문항풀이 순서', icon: '📋' },
                    'goal': { name: '목표점수 조정', icon: '🎯' },
                    'cost': { name: '기회비용 계산', icon: '💰' }
                },
                dashboard: [
                    {
                        title: '모의고사 횟수',
                        icon: '📄',
                        content: '이번 달: 8회',
                        progress: 80,
                        subcategory: 'time'
                    },
                    {
                        title: '시간 관리',
                        icon: '⏰',
                        content: '평균 완료: 48분/50분',
                        progress: 96,
                        subcategory: 'time'
                    },
                    {
                        title: '실수율',
                        icon: '🎯',
                        content: '계산 실수: 5%',
                        progress: 95,
                        subcategory: 'mistake'
                    },
                    {
                        title: '전략 점수',
                        icon: '📊',
                        content: '풀이 순서 최적화: 85점',
                        progress: 85,
                        subcategory: 'order'
                    }
                ],
                menu: {
                    'time': [
                        { icon: '⏱️', title: '속도 훈련', desc: '문제별 시간 배분' },
                        { icon: '⚡', title: '빠른 판단', desc: '건너뛰기 결정' },
                        { icon: '📊', title: '시간 분석', desc: '소요 시간 통계' },
                        { icon: '🎯', title: '목표 시간', desc: '적정 속도 찾기' }
                    ],
                    'mistake': [
                        { icon: '🔍', title: '실수 패턴', desc: '반복 실수 분석' },
                        { icon: '✅', title: '검토 방법', desc: '효율적 재검토' },
                        { icon: '🎯', title: '집중력 관리', desc: '실수 방지 전략' },
                        { icon: '📝', title: '실수 노트', desc: '오류 기록 관리' }
                    ],
                    'order': [
                        { icon: '📋', title: '난이도별 순서', desc: '쉬운 문제 먼저' },
                        { icon: '🎯', title: '배점별 전략', desc: '고배점 우선순위' },
                        { icon: '⏰', title: '시간별 배분', desc: '문항당 시간 계획' },
                        { icon: '🔄', title: '유연한 조정', desc: '상황별 전략 변경' }
                    ]
                }
            },
            attendance: {
                title: '출결관리',
                icon: '📅',
                desc: '규칙적인 학습 습관 형성',
                subcategories: {
                    'makeup': { name: '사전보강', icon: '📚' },
                    'comprehensive': { name: '전수보강', icon: '📖' },
                    'routine': { name: '일정공유 루틴', icon: '🔄' }
                },
                dashboard: [
                    {
                        title: '이번 달 출석',
                        icon: '✅',
                        content: '출석률: 95%',
                        progress: 95,
                        subcategory: 'routine'
                    },
                    {
                        title: '연속 출석',
                        icon: '🔥',
                        content: '12일 연속 출석 중',
                        progress: 100,
                        subcategory: 'routine'
                    },
                    {
                        title: '보강 현황',
                        icon: '📚',
                        content: '완료: 3/3회',
                        progress: 100,
                        subcategory: 'makeup'
                    },
                    {
                        title: '일정 동기화',
                        icon: '🔄',
                        content: '가족 캘린더 연동됨',
                        progress: 100,
                        subcategory: 'routine'
                    }
                ],
                menu: {
                    'makeup': [
                        { icon: '📅', title: '보강 일정', desc: '미리 계획하기' },
                        { icon: '📚', title: '학습 내용', desc: '놓친 수업 확인' },
                        { icon: '🎥', title: '녹화 강의', desc: '온라인 보충' },
                        { icon: '📝', title: '과제 확인', desc: '빠진 과제 체크' }
                    ],
                    'comprehensive': [
                        { icon: '📖', title: '전체 복습', desc: '단원별 총정리' },
                        { icon: '🔄', title: '진도 맞추기', desc: '현재 진도 동기화' },
                        { icon: '💬', title: '개별 상담', desc: '1:1 보충 수업' },
                        { icon: '📊', title: '성과 측정', desc: '보강 효과 확인' }
                    ],
                    'routine': [
                        { icon: '📅', title: '가족 캘린더', desc: '일정 공유하기' },
                        { icon: '🔔', title: '알림 설정', desc: '수업 리마인더' },
                        { icon: '📱', title: '모바일 연동', desc: '실시간 확인' },
                        { icon: '👨‍👩‍👧', title: '가족 소통', desc: '학습 현황 공유' }
                    ]
                }
            }
        };

        // 초기화
        window.onload = function() {
            selectCategory('concept');
            loadDashboard();
            checkChainInteraction();
            
            // 엔터키 이벤트
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        };

        // 카테고리 선택
        function selectCategory(category) {
            currentCategory = category;
            currentSubcategory = null;
            
            // 사이드바 활성화 상태 업데이트
            document.querySelectorAll('.category-header').forEach(header => {
                header.classList.remove('active');
            });
            event.target.closest('.category-header').classList.add('active');
            
            // 섹션 정보 업데이트
            const data = categoryData[category];
            document.getElementById('sectionAvatar').textContent = data.icon;
            document.getElementById('sectionTitle').textContent = data.title;
            document.getElementById('sectionDesc').textContent = data.desc;
            
            // 서브카테고리 탭 업데이트
            updateSubcategoryTabs();
            
            // 현재 모드에 따라 콘텐츠 로드
            if (currentMode === 'dashboard') {
                loadDashboard();
            } else if (currentMode === 'menu') {
                loadMenu();
            }
            
            // 모바일에서 카테고리 선택 시 메뉴 닫기
            if (window.innerWidth <= 768) {
                closeMobileMenu();
            }
        }

        // 서브카테고리 탭 업데이트
        function updateSubcategoryTabs() {
            const data = categoryData[currentCategory];
            const tabsContainer = document.getElementById('tabsContainer');
            const subcategoryTabs = document.getElementById('subcategoryTabs');
            
            if (data.subcategories && Object.keys(data.subcategories).length > 0) {
                subcategoryTabs.classList.add('active');
                
                tabsContainer.innerHTML = Object.entries(data.subcategories).map(([key, sub]) => `
                    <div class="tab-item ${!currentSubcategory ? 'active' : currentSubcategory === key ? 'active' : ''}" 
                         onclick="selectSubcategory('${key}')">
                        ${sub.icon} ${sub.name}
                    </div>
                `).join('');
            } else {
                subcategoryTabs.classList.remove('active');
            }
        }

        // 서브카테고리 선택
        function selectSubcategory(subcategory) {
            currentSubcategory = subcategory;
            
            // 탭 활성화 상태
            document.querySelectorAll('.tab-item').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 콘텐츠 필터링
            if (currentMode === 'dashboard') {
                loadDashboard();
            } else if (currentMode === 'menu') {
                loadMenu();
            }
        }

        // 모드 전환
        function switchMode(mode) {
            currentMode = mode;
            
            // 버튼 활성화 상태
            document.querySelectorAll('.mode-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 콘텐츠 영역 표시/숨김
            document.getElementById('dashboardMode').classList.remove('active');
            document.getElementById('menuMode').classList.remove('active');
            
            if (mode === 'dashboard') {
                document.getElementById('dashboardMode').classList.add('active');
                loadDashboard();
            } else if (mode === 'menu') {
                document.getElementById('menuMode').classList.add('active');
                loadMenu();
            } else if (mode === 'chat') {
                // 채팅 패널 열기
                openChatPanel();
            }
        }
        
        // 채팅 패널 열기
        function openChatPanel() {
            document.getElementById('chatPanel').classList.add('active');
            document.getElementById('chatPanelOverlay').classList.add('active');
            initChat();
        }
        
        // 채팅 패널 닫기
        function closeChatPanel() {
            document.getElementById('chatPanel').classList.remove('active');
            document.getElementById('chatPanelOverlay').classList.remove('active');
            // 대시보드 모드로 돌아가기
            document.querySelector('.mode-button[onclick="switchMode(\'dashboard\')"]').click();
        }

        // 대시보드 로드
        function loadDashboard() {
            const data = categoryData[currentCategory];
            const grid = document.getElementById('dashboardGrid');
            
            let dashboardData = data.dashboard;
            if (currentSubcategory) {
                dashboardData = dashboardData.filter(item => item.subcategory === currentSubcategory);
            }
            
            grid.innerHTML = dashboardData.map(item => `
                <div class="dashboard-card" onclick="handleDashboardClick('${item.subcategory}', '${item.title}')">
                    <div class="card-header">
                        <h3 class="card-title">${item.title}</h3>
                        <div class="card-icon">${item.icon}</div>
                    </div>
                    <div class="card-content">
                        ${item.content}
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${item.progress}%"></div>
                    </div>
                </div>
            `).join('');
        }

        // 메뉴 로드
        function loadMenu() {
            const data = categoryData[currentCategory];
            const grid = document.getElementById('menuTabGrid');
            
            let menuData = [];
            if (currentSubcategory && data.menu[currentSubcategory]) {
                menuData = data.menu[currentSubcategory];
            } else if (!currentSubcategory && data.menu) {
                // 모든 서브카테고리의 메뉴 표시
                Object.values(data.menu).forEach(items => {
                    menuData = menuData.concat(items);
                });
            }
            
            grid.innerHTML = menuData.map(item => `
                <div class="menu-tab-item" onclick="handleMenuClick('${item.title}')">
                    <div class="menu-tab-icon">${item.icon}</div>
                    <div class="menu-tab-title">${item.title}</div>
                    <div class="menu-tab-desc">${item.desc}</div>
                </div>
            `).join('');
        }

        // 연쇄상호작용 체크
        function checkChainInteraction() {
            // 비슷한 학습 패턴을 가진 학생 찾기 시뮬레이션
            const hasChainPartner = Math.random() > 0.7; // 30% 확률로 파트너 있음
            
            if (hasChainPartner && currentMode === 'dashboard') {
                const container = document.getElementById('dashboardGrid');
                const notification = `
                    <div class="chain-notification">
                        <span>🔗</span>
                        <span>비슷한 학습 패턴을 가진 3명의 학생과 연결되었습니다!</span>
                        <button onclick="joinChainSession()" style="margin-left: auto; background: white; color: #f59e0b; border: none; padding: 0.25rem 0.75rem; border-radius: 0.25rem; cursor: pointer;">
                            참여하기
                        </button>
                    </div>
                `;
                container.insertAdjacentHTML('afterbegin', notification);
            }
        }

        // 연쇄상호작용 세션 참여
        function joinChainSession() {
            alert('연쇄상호작용 학습 세션에 참여합니다. 비슷한 수준의 학생들과 함께 학습하세요!');
            switchMode('chat');
            addMessage('ai', '🔗 연쇄상호작용 세션이 시작되었습니다. 현재 3명의 학생이 함께 참여중입니다.');
        }

        // 채팅 초기화
        function initChat() {
            const container = document.getElementById('chatContainer');
            if (container.children.length === 0) {
                addMessage('ai', '안녕하세요! 메타인지 학습 도우미입니다. 인지관성을 개선하고 효과적인 학습을 도와드릴게요. 무엇을 도와드릴까요?');
            }
        }

        // 메시지 전송
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            addMessage('user', message);
            input.value = '';
            
            // AI 응답 시뮬레이션
            setTimeout(() => {
                const response = getAIResponse(message);
                addMessage('ai', response);
            }, 1000);
        }

        // 메시지 추가
        function addMessage(sender, text) {
            const container = document.getElementById('chatContainer');
            const messageHTML = `
                <div class="chat-message">
                    <div class="message-avatar">
                        ${sender === 'ai' ? '🤖' : '👤'}
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-name">${sender === 'ai' ? 'AI 메타인지 도우미' : '나'}</span>
                            <span class="message-time">${new Date().toLocaleTimeString()}</span>
                        </div>
                        <div class="message-text">${text}</div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', messageHTML);
            container.scrollTop = container.scrollHeight;
        }

        // AI 응답 생성
        function getAIResponse(message) {
            const responses = {
                '포모도로': '포모도로 기법은 25분 집중, 5분 휴식을 반복하는 시간 관리 방법입니다. 지금 바로 타이머를 시작하시겠어요?',
                '개념': '개념 학습을 위해서는 먼저 전체적인 구조를 파악한 후, 세부 내용을 채워나가는 것이 효과적입니다. 어떤 개념을 학습하시나요?',
                '문제': '문제 풀이 시작 전에 문제를 꼼꼼히 읽고 주어진 조건을 정리하는 것이 중요해요. 어떤 유형의 문제인가요?',
                '시험': '시험 준비는 계획적으로 하는 것이 중요합니다. 현재 준비 상태를 진단해보시겠어요?',
                '실수': '실수를 줄이기 위해서는 패턴을 분석하고 체크리스트를 만드는 것이 도움됩니다. 어떤 종류의 실수가 자주 발생하나요?',
                '연쇄': '연쇄상호작용은 비슷한 학습 패턴을 가진 학생들과 함께 학습하는 방법입니다. 현재 3명의 학생이 대기 중입니다.',
                default: '네, 이해했습니다. 더 구체적으로 어떤 부분에서 도움이 필요하신가요? 메타인지 향상을 위한 맞춤형 조언을 드릴게요.'
            };
            
            for (let key in responses) {
                if (message.includes(key)) {
                    return responses[key];
                }
            }
            
            return responses.default;
        }

        // 대시보드 클릭 핸들러
        function handleDashboardClick(subcategory, title) {
            currentSubcategory = subcategory;
            updateSubcategoryTabs();
            switchMode('menu');
        }

        // 메뉴 클릭 핸들러
        function handleMenuClick(title) {
            alert(`${title} 기능을 실행합니다.`);
        }

        // 윈도우 리사이즈 시 모바일 메뉴 초기화
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
        
        // 터치 스와이프로 메뉴 닫기
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar.classList.contains('active')) {
                if (touchEndX < touchStartX - 50) {
                    closeMobileMenu();
                }
            }
        }

        // 모바일에서 스크롤 성능 최적화
        const subcategoryTabs = document.querySelector('.tabs-container');
        if (subcategoryTabs) {
            subcategoryTabs.addEventListener('touchmove', (e) => {
                e.stopPropagation();
            }, { passive: true });
        }
        
        // 미니맵 토글 (이미 정의된 함수와 충돌 방지)
        if (!window.toggleMinimap) {
            window.toggleMinimap = function() {
                const dropdown = document.getElementById('minimapDropdown');
                dropdown.classList.toggle('active');
            }
        }
        
        // 클릭 외부 영역 감지 (이미 정의된 이벤트와 충돌 방지)
        if (!window.minimapClickHandler) {
            window.minimapClickHandler = true;
            document.addEventListener('click', function(event) {
                const minimap = document.getElementById('minimapDropdown');
                const button = document.querySelector('.minimap-button');
                
                if (minimap && button && !minimap.contains(event.target) && !button.contains(event.target)) {
                    minimap.classList.remove('active');
                }
            });
        }
    </script>
    
    <!-- 채팅 패널 오버레이 -->
    <div class="chat-panel-overlay" id="chatPanelOverlay" onclick="closeChatPanel()"></div>
    
    <!-- 채팅 패널 -->
    <div class="chat-panel" id="chatPanel">
        <div class="chat-panel-header">
            <h3 class="chat-panel-title">💬 AI 학습 상담</h3>
            <button class="chat-panel-close" onclick="closeChatPanel()">×</button>
        </div>
        <div class="chat-area">
            <div class="chat-container" id="chatContainer">
                <!-- 채팅 메시지들이 동적으로 생성됩니다 -->
            </div>
        </div>
        <div class="chat-panel-input">
            <div class="chat-panel-input-wrapper">
                <input type="text" id="messageInput" placeholder="궁금한 것을 물어보세요..." onkeypress="if(event.key === 'Enter') sendMessage()">
                <button onclick="sendMessage()">전송</button>
            </div>
        </div>
    </div>
</body>
</html>