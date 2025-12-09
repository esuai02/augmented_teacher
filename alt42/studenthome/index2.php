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
    <title>심화학습 - Math Learning Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #8b7da8 0%, #a8b4d0 100%);
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

        .nav-button {
            padding: 0.5rem 1rem;
            background: #7b6d95;
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
            background: #6a5d82;
            transform: translateY(-2px);
        }

        .stats-display {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .stat-item {
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
            background: linear-gradient(135deg, #7b6d95, #5a4d6e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            text-align: center;
        }

        .welcome-subtitle {
            color: #333;
            font-size: 1.25rem;
            margin-bottom: 3rem;
            text-align: center;
            text-shadow: 0 1px 2px rgba(255,255,255,0.5);
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
            border-color: #fa709a;
            background: #fa709a;
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
            border-color: #fa709a;
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

        /* 문제풀이 카드 */
        .problem-card {
            --gradient-start: #9b88b4;
            --gradient-end: #7a6a8f;
        }

        /* 심화개념 카드 */
        .advanced-card {
            --gradient-start: #6a8caf;
            --gradient-end: #4a6b8a;
        }

        /* 경시대회 카드 */
        .competition-card {
            --gradient-start: #a4a09b;
            --gradient-end: #7a7672;
        }

        /* 챌린지 모드 버튼 */
        .challenge-button {
            width: 100%;
            max-width: 965px;
            padding: 1.5rem;
            background: linear-gradient(135deg, #8a7fa4, #6a8caf);
            border: none;
            border-radius: 1rem;
            color: white;
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

        .challenge-button:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        /* 레벨 1 - 문제 선택 */
        .level-1 {
            display: none;
        }

        .section-title {
            text-align: center;
            color: #333;
            font-size: 3rem;
            margin-bottom: 3rem;
            text-shadow: 0 1px 2px rgba(255,255,255,0.5);
        }

        .difficulty-selector {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .difficulty-button {
            padding: 1rem 2rem;
            background: white;
            border: 3px solid transparent;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .difficulty-button:hover {
            transform: translateY(-2px);
        }

        .difficulty-button.active {
            border-color: #fa709a;
            background: #fa709a;
            color: white;
        }

        .problem-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .problem-item {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: relative;
        }

        .problem-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .problem-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .problem-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #333;
        }

        .problem-difficulty {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: bold;
        }

        .difficulty-easy {
            background: #d4edda;
            color: #155724;
        }

        .difficulty-medium {
            background: #fff3cd;
            color: #856404;
        }

        .difficulty-hard {
            background: #f8d7da;
            color: #721c24;
        }

        /* 레벨 2 - 챌린지 모드 */
        .level-2 {
            display: none;
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .challenge-header {
            background: linear-gradient(135deg, #ff6b6b, #4ecdc4);
            padding: 2rem;
            border-radius: 1rem;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .challenge-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            min-height: 600px;
        }

        /* 문제 영역 */
        .problem-area {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 2rem;
        }

        .timer-bar {
            background: #e0e0e0;
            height: 0.5rem;
            border-radius: 0.25rem;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .timer-fill {
            background: linear-gradient(to right, #ff6b6b, #ffa500);
            height: 100%;
            width: 100%;
            transition: width 1s linear;
        }

        .problem-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .problem-content h3 {
            color: #fa709a;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .problem-statement {
            margin-bottom: 2rem;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .solution-area {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            min-height: 200px;
        }

        .solution-textarea {
            width: 100%;
            min-height: 150px;
            border: none;
            resize: vertical;
            font-size: 1rem;
            line-height: 1.6;
            outline: none;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .submit-button {
            padding: 1rem 2rem;
            background: #4ecdc4;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: all 0.3s;
        }

        .submit-button:hover {
            background: #45b7b8;
        }

        .hint-button {
            padding: 1rem 2rem;
            background: #fee140;
            color: #333;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: all 0.3s;
        }

        .hint-button:hover {
            background: #ffd93d;
        }

        /* 리더보드 영역 */
        .leaderboard-area {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .leaderboard-header {
            background: linear-gradient(135deg, #fa709a, #fee140);
            padding: 1rem;
            border-radius: 0.5rem;
            color: white;
            text-align: center;
            margin-bottom: 1rem;
        }

        .leaderboard-list {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            background: #f8f9fa;
        }

        .leaderboard-rank {
            font-size: 1.5rem;
            font-weight: bold;
            width: 40px;
            text-align: center;
        }

        .rank-1 { color: #ffd700; }
        .rank-2 { color: #c0c0c0; }
        .rank-3 { color: #cd7f32; }

        .leaderboard-name {
            flex: 1;
            margin-left: 1rem;
            font-weight: 600;
        }

        .leaderboard-score {
            font-weight: bold;
            color: #fa709a;
        }

        /* 성취도 표시 */
        .achievement-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            text-align: center;
            display: none;
            z-index: 1000;
        }

        .achievement-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }

        .achievement-title {
            font-size: 2rem;
            color: #7b6d95;
            margin-bottom: 0.5rem;
        }

        /* 미니맵 */
        .minimap-button {
            padding: 0.5rem 1rem;
            background: #7b6d95;
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
            background: #6a5d82;
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
            background: #7b6d95;
            color: white;
            font-weight: bold;
        }

        /* 학년 선택 */
        .grade-selector {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .grade-button {
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid transparent;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1.1rem;
            color: #333;
        }
        
        .grade-button:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .grade-button.active {
            background: white;
            border-color: #7b6d95;
            color: #7b6d95;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* 과목 선택 */
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            max-width: 900px;
            margin: 0 auto 2rem;
        }
        
        .subject-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid #f0f0f0;
            position: relative;
        }
        
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #7b6d95;
        }
        
        .subject-card.selected {
            background: linear-gradient(135deg, #8b7da8, #a8b4d0);
            color: white;
            border-color: transparent;
        }
        
        .subject-card.selected::after {
            content: '✓';
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }
        
        .subject-card.selected h3,
        .subject-card.selected p {
            color: white;
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

        .bounce {
            animation: bounce 0.5s ease-in-out;
        }

        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* 반응형 */
        @media (max-width: 1024px) {
            .main-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .challenge-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-cards {
                grid-template-columns: 1fr;
            }
            
            .welcome-title {
                font-size: 2.5rem;
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
        // 공통 헤더 포함
        $active_page = 'index2';
        include_once('includes/header.php');
        ?>

        <!-- 메인 컨테이너 -->
    <div class="main-container" id="main-container">
        <!-- 레벨 0: 메인 대시보드 -->
        <div class="level-0" id="level-0">
            <h1 class="welcome-title">심화학습 센터</h1>
            <p class="welcome-subtitle">도전적인 문제로 실력을 한 단계 끌어올리세요</p>
            
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
            
            <button class="challenge-button" onclick="navigateToChallenge()">
                <span>⚡</span>
                <span>챌린지 모드 시작</span>
                <span>🏆</span>
            </button>
        </div>

        <!-- 레벨 1: 문제 선택 -->
        <div class="level-1" id="level-1">
            <h1 class="section-title" id="section-title">고난도 문제</h1>
            
            <div class="difficulty-selector">
                <button class="difficulty-button active" onclick="selectDifficulty('중급')">
                    중급
                </button>
                <button class="difficulty-button" onclick="selectDifficulty('상급')">
                    상급
                </button>
                <button class="difficulty-button" onclick="selectDifficulty('최상급')">
                    최상급
                </button>
            </div>
            
            <div class="problem-grid" id="problem-grid">
                <!-- 동적으로 생성됨 -->
            </div>
        </div>

        <!-- 레벨 2: 챌린지 모드 -->
        <div class="level-2" id="level-2">
            <div class="challenge-header">
                <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">⚡ 챌린지 모드</h2>
                <p>시간 제한 내에 문제를 해결하고 리더보드에 도전하세요!</p>
            </div>

            <div class="challenge-content">
                <!-- 문제 영역 -->
                <div class="problem-area">
                    <div class="timer-bar">
                        <div class="timer-fill" id="timer-fill"></div>
                    </div>

                    <div class="problem-content">
                        <h3>챌린지 문제 #1</h3>
                        <div class="problem-statement">
                            <p>다음 수열의 일반항을 구하고, 100번째 항의 값을 구하시오.</p>
                            <p style="margin-top: 1rem; font-size: 1.2rem; text-align: center;">
                                <strong>1, 3, 7, 15, 31, ...</strong>
                            </p>
                        </div>
                    </div>

                    <div class="solution-area">
                        <textarea class="solution-textarea" placeholder="여기에 풀이를 작성하세요..."></textarea>
                    </div>

                    <div class="action-buttons">
                        <button class="hint-button" onclick="showHint()">
                            💡 힌트 보기 (-50점)
                        </button>
                        <button class="submit-button" onclick="submitSolution()">
                            제출하기
                        </button>
                    </div>
                </div>

                <!-- 리더보드 영역 -->
                <div class="leaderboard-area">
                    <div class="leaderboard-header">
                        <h3 style="margin: 0;">🏆 리더보드</h3>
                    </div>
                    
                    <div class="leaderboard-list">
                        <div class="leaderboard-item">
                            <span class="leaderboard-rank rank-1">1</span>
                            <span class="leaderboard-name">수학천재</span>
                            <span class="leaderboard-score">9850</span>
                        </div>
                        <div class="leaderboard-item">
                            <span class="leaderboard-rank rank-2">2</span>
                            <span class="leaderboard-name">도전자123</span>
                            <span class="leaderboard-score">9720</span>
                        </div>
                        <div class="leaderboard-item">
                            <span class="leaderboard-rank rank-3">3</span>
                            <span class="leaderboard-name">문제풀이왕</span>
                            <span class="leaderboard-score">9650</span>
                        </div>
                        <div class="leaderboard-item">
                            <span class="leaderboard-rank">4</span>
                            <span class="leaderboard-name">열공맨</span>
                            <span class="leaderboard-score">9500</span>
                        </div>
                        <div class="leaderboard-item">
                            <span class="leaderboard-rank">5</span>
                            <span class="leaderboard-name">수학러버</span>
                            <span class="leaderboard-score">9420</span>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; padding: 1rem; background: white; border-radius: 0.5rem;">
                        <h4 style="color: #7b6d95; margin-bottom: 0.5rem;">나의 기록</h4>
                        <p style="font-size: 0.9rem; color: #666;">최고 점수: <strong id="my-best-score">0</strong></p>
                        <p style="font-size: 0.9rem; color: #666;">오늘 도전: <strong id="today-attempts">0</strong>회</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 성취도 팝업 -->
    <div class="achievement-popup" id="achievement-popup">
        <div class="achievement-icon">🏆</div>
        <h2 class="achievement-title">축하합니다!</h2>
        <p id="achievement-message">문제를 성공적으로 해결했습니다!</p>
        <button class="nav-button" style="margin-top: 1rem;" onclick="closeAchievement()">
            계속하기
        </button>
    </div>

    <script src="js/save_selection.js"></script>
    <script>
        // 전역 변수
        let currentLevel = 0;
        let currentPath = [];
        let studyTime = 0;
        
        // 사용자 정보
        const studentId = <?php echo $studentid; ?>;
        const pageType = 'index2';
        let score = 0;
        let streak = 0;
        let currentDifficulty = '중급';
        let challengeTimer = null;
        let timeRemaining = 300; // 5분

        // 학년별 과목 데이터
        const advancedSubjects = {
            elementary: {
                title: '초등 심화수학',
                subjects: [
                    { code: '4-1', name: '초등수학 4-1', desc: '4학년 1학기 심화과정' },
                    { code: '4-2', name: '초등수학 4-2', desc: '4학년 2학기 심화과정' },
                    { code: '5-1', name: '초등수학 5-1', desc: '5학년 1학기 심화과정' },
                    { code: '5-2', name: '초등수학 5-2', desc: '5학년 2학기 심화과정' },
                    { code: '6-1', name: '초등수학 6-1', desc: '6학년 1학기 심화과정' },
                    { code: '6-2', name: '초등수학 6-2', desc: '6학년 2학기 심화과정' }
                ]
            },
            middle: {
                title: '중등 심화수학',
                subjects: [
                    { code: '1-1', name: '중등수학 1-1', desc: '중학교 1학년 1학기 심화' },
                    { code: '1-2', name: '중등수학 1-2', desc: '중학교 1학년 2학기 심화' },
                    { code: '2-1', name: '중등수학 2-1', desc: '중학교 2학년 1학기 심화' },
                    { code: '2-2', name: '중등수학 2-2', desc: '중학교 2학년 2학기 심화' },
                    { code: '3-1', name: '중등수학 3-1', desc: '중학교 3학년 1학기 심화' },
                    { code: '3-2', name: '중등수학 3-2', desc: '중학교 3학년 2학기 심화' },
                    { code: 'kmc', name: '경시준비_KMC', desc: '한국수학경시대회 준비' },
                    { code: 'kmo', name: '경시준비_KMO', desc: '한국수학올림피아드 준비' },
                    { code: 'special', name: '특목대비', desc: '특목고 입시 대비' }
                ]
            },
            high: {
                title: '고등 심화수학',
                subjects: [
                    { code: 'common1', name: '공통수학 1', desc: '고등학교 공통과정 심화' },
                    { code: 'common2', name: '공통수학 2', desc: '고등학교 공통과정 심화' },
                    { code: 'algebra', name: '대수', desc: '선택과목 심화' },
                    { code: 'calculus1', name: '미적분 I', desc: '선택과목 심화' },
                    { code: 'statistics', name: '확률과 통계', desc: '선택과목 심화' },
                    { code: 'calculus2', name: '미적분 II', desc: '선택과목 심화' },
                    { code: 'geometry', name: '기하', desc: '선택과목 심화' }
                ]
            }
        };
        
        let currentGrade = 'elementary';
        
        // 문제 데이터
        const problemData = {
            '고난도문제': {
                '중급': [
                    { title: '조합론 문제', type: '조합론', time: '30분', points: 100 },
                    { title: '정수론 문제', type: '정수론', time: '25분', points: 90 },
                    { title: '기하 문제', type: '기하', time: '35분', points: 110 }
                ],
                '상급': [
                    { title: '함수방정식', type: '함수', time: '40분', points: 150 },
                    { title: '부등식 증명', type: '대수', time: '45분', points: 160 },
                    { title: '확률론 응용', type: '확률', time: '40분', points: 140 }
                ],
                '최상급': [
                    { title: 'IMO 기출', type: '종합', time: '60분', points: 200 },
                    { title: 'Putnam 문제', type: '대학수학', time: '90분', points: 250 },
                    { title: '연구 문제', type: '미해결', time: '무제한', points: 500 }
                ]
            },
            '심화개념': {
                '중급': [
                    { title: '위상수학 입문', type: '이론', time: '학습형', points: 80 },
                    { title: '추상대수 기초', type: '이론', time: '학습형', points: 85 },
                    { title: '해석학 개론', type: '이론', time: '학습형', points: 90 }
                ],
                '상급': [
                    { title: '갈루아 이론', type: '대수', time: '학습형', points: 120 },
                    { title: '리만 기하학', type: '기하', time: '학습형', points: 130 },
                    { title: '측도론', type: '해석', time: '학습형', points: 125 }
                ],
                '최상급': [
                    { title: '대수적 위상수학', type: '위상', time: '학습형', points: 180 },
                    { title: '대수기하학', type: '기하', time: '학습형', points: 190 },
                    { title: '범주론', type: '추상', time: '학습형', points: 200 }
                ]
            },
            '경시대회': {
                '중급': [
                    { title: 'KMO 예선', type: '경시', time: '90분', points: 150 },
                    { title: 'AMC 12', type: '경시', time: '75분', points: 140 },
                    { title: 'AIME 준비', type: '경시', time: '180분', points: 160 }
                ],
                '상급': [
                    { title: 'KMO 본선', type: '경시', time: '240분', points: 200 },
                    { title: 'USAMO', type: '경시', time: '270분', points: 220 },
                    { title: 'BMO', type: '경시', time: '210분', points: 210 }
                ],
                '최상급': [
                    { title: 'IMO 준비', type: '국제', time: '270분', points: 300 },
                    { title: 'Putnam', type: '대학', time: '360분', points: 350 },
                    { title: 'Fields 도전', type: '연구', time: '무제한', points: 1000 }
                ]
            }
        };

        // 초기화
        window.onload = function() {
            startTimer();
            loadProgress();
        };

        // 타이머
        function startTimer() {
            setInterval(() => {
                studyTime++;
                updateTimerDisplay();
            }, 1000);
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(studyTime / 60);
            const seconds = studyTime % 60;
            document.getElementById('study-timer').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        // 네비게이션
        function navigateToProblems(type) {
            currentPath = [type];
            showProblems(type);
            navigateToLevel(1);
        }

        function navigateToChallenge() {
            currentPath = ['챌린지 모드'];
            navigateToLevel(2);
            startChallenge();
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
                if (challengeTimer) {
                    clearInterval(challengeTimer);
                    challengeTimer = null;
                }
                navigateToLevel(currentLevel - 1);
                currentPath.pop();
            }
        }

        // 난이도 선택
        function selectDifficulty(difficulty) {
            currentDifficulty = difficulty;
            document.querySelectorAll('.difficulty-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            showProblems(currentPath[0]);
        }

        // 문제 표시
        function showProblems(type) {
            const container = document.getElementById('problem-grid');
            const problems = problemData[type][currentDifficulty];
            document.getElementById('section-title').textContent = type;
            
            container.innerHTML = problems.map((problem, index) => `
                <div class="problem-item" onclick="startProblem('${type}', ${index})">
                    <div class="problem-header">
                        <div class="problem-title">${problem.title}</div>
                        <div class="problem-difficulty difficulty-${currentDifficulty === '중급' ? 'easy' : currentDifficulty === '상급' ? 'medium' : 'hard'}">
                            ${currentDifficulty}
                        </div>
                    </div>
                    <p style="color: #666; margin-bottom: 1rem;">유형: ${problem.type}</p>
                    <p style="color: #999; font-size: 0.9rem;">시간: ${problem.time}</p>
                    <p style="color: #7b6d95; font-weight: bold;">+${problem.points}점</p>
                </div>
            `).join('');
        }

        // 문제 시작
        function startProblem(type, index) {
            const problem = problemData[type][currentDifficulty][index];
            alert(`${problem.title} 문제를 시작합니다!\n\n제한시간: ${problem.time}\n획득가능 점수: ${problem.points}점`);
            addScore(problem.points);
        }

        // 챌린지 모드
        function startChallenge() {
            timeRemaining = 300;
            updateChallengeTimer();
            
            challengeTimer = setInterval(() => {
                timeRemaining--;
                updateChallengeTimer();
                
                if (timeRemaining <= 0) {
                    clearInterval(challengeTimer);
                    alert('시간 초과! 다시 도전해보세요.');
                    goBack();
                }
            }, 1000);
        }

        function updateChallengeTimer() {
            const percentage = (timeRemaining / 300) * 100;
            document.getElementById('timer-fill').style.width = percentage + '%';
            
            if (percentage < 20) {
                document.getElementById('timer-fill').style.background = 'linear-gradient(to right, #ff0000, #ff6b6b)';
            }
        }

        // 힌트 보기
        function showHint() {
            if (confirm('힌트를 보시겠습니까? (50점 차감)')) {
                alert('힌트: 각 항은 2^n - 1 형태입니다.');
                addScore(-50);
            }
        }

        // 답안 제출
        function submitSolution() {
            const solution = document.querySelector('.solution-textarea').value;
            if (!solution.trim()) {
                alert('답안을 작성해주세요!');
                return;
            }
            
            clearInterval(challengeTimer);
            
            // 정답 체크 (실제로는 서버에서 처리)
            if (solution.includes('2^n - 1') || solution.includes('2^100 - 1')) {
                showAchievement('정답입니다! 🎉', 300);
                updateLeaderboard();
            } else {
                showAchievement('아쉽네요. 다시 도전해보세요!', 50);
            }
        }

        // 성취도 표시
        function showAchievement(message, points) {
            const popup = document.getElementById('achievement-popup');
            document.getElementById('achievement-message').textContent = message;
            popup.style.display = 'block';
            popup.classList.add('bounce');
            
            addScore(points);
            
            const attempts = parseInt(localStorage.getItem('todayAttempts') || '0') + 1;
            localStorage.setItem('todayAttempts', attempts);
            document.getElementById('today-attempts').textContent = attempts;
        }

        function closeAchievement() {
            document.getElementById('achievement-popup').style.display = 'none';
            goBack();
        }

        // 점수 관리
        function addScore(points) {
            score += points;
            document.getElementById('score-display').textContent = score + '점';
            
            const bestScore = parseInt(localStorage.getItem('bestScore') || '0');
            if (score > bestScore) {
                localStorage.setItem('bestScore', score);
                document.getElementById('my-best-score').textContent = score;
            }
        }

        // 리더보드 업데이트
        function updateLeaderboard() {
            // 실제로는 서버와 통신
            const myScore = score;
            // 리더보드 재정렬 로직
        }

        // 과목 표시 및 선택 기능
        let selectedAdvancedSubject = null;
        
        // 과목 표시
        function showSubjects(grade) {
            const container = document.getElementById('subject-grid');
            const data = advancedSubjects[grade];
            
            container.innerHTML = data.subjects.map((subject, index) => `
                <div class="subject-card" id="advanced-subject-${index}" onclick="selectAdvancedSubject('${grade}', '${subject.code}', '${subject.name}', ${index})">
                    <h3>${subject.name}</h3>
                    <p>${subject.desc}</p>
                </div>
            `).join('');
        }
        
        // 심화 과목 선택
        function selectAdvancedSubject(grade, code, name, index) {
            // 이전 선택 해제
            if (selectedAdvancedSubject !== null) {
                document.getElementById(`advanced-subject-${selectedAdvancedSubject}`).classList.remove('selected');
            }
            
            // 새 선택 적용
            selectedAdvancedSubject = index;
            document.getElementById(`advanced-subject-${index}`).classList.add('selected');
            
            // 최근 학습 저장
            const recentCourse = {
                type: '심화학습',
                grade: grade,
                subject: name,
                code: code,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('recentCourse', JSON.stringify(recentCourse));
            
            // 서버에 선택 정보 저장
            saveUserSelection(pageType, name, code, {
                grade: grade,
                difficulty: currentDifficulty,
                subject: selectedAdvancedSubject,
                path: currentGrade
            });
            
            // 각 과목별 링크로 이동
            let url = '';
            
            if (grade === 'elementary') {
                // 초등수학 링크
                const checklistMap = {
                    '4-1': 40055,
                    '4-2': 40056,
                    '5-1': 40054,
                    '5-2': 40057,
                    '6-1': 40058,
                    '6-2': 40059
                };
                if (checklistMap[code]) {
                    // studentid 제거
                    url = `https://mathking.kr/moodle/mod/checklist/view.php?id=${checklistMap[code]}`;
                }
            } else if (grade === 'middle') {
                // 중등수학 링크
                if (code === 'kmc') {
                    url = 'https://mathking.kr/moodle/mod/checklist/index.php?id=142';
                } else if (code === 'kmo') {
                    url = 'https://mathking.kr/moodle/mod/checklist/view.php?id=4186';
                } else if (code === 'special') {
                    url = 'https://mathking.kr/moodle/mod/checklist/index.php?id=275';
                } else {
                    const cidMap = {
                        '1-1': 24,
                        '1-2': 25,
                        '2-1': 26,
                        '2-2': 27,
                        '3-1': 28,
                        '3-2': 29
                    };
                    if (cidMap[code]) {
                        url = `https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=2&cid=${cidMap[code]}&tb=90`;
                    }
                }
            } else if (grade === 'high') {
                // 고등수학 링크
                const cidMap = {
                    'common1': 1,
                    'common2': 30,
                    'algebra': 31,
                    'calculus1': 32,
                    'statistics': 35,
                    'calculus2': 33,
                    'geometry': 34
                };
                if (cidMap[code]) {
                    url = `https://mathking.kr/moodle/local/augmented_teacher/students/missionhome.php?mtid=2&cid=${cidMap[code]}&tb=90`;
                }
            }
            
            if (url) {
                // 현재 창에서 열기
                window.location.href = url;
            } else {
                // 챌린지 버튼으로 스크롤
                setTimeout(() => {
                    document.querySelector('.challenge-button').scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 300);
            }
        }
        
        // 학년 선택
        function selectGrade(grade) {
            currentGrade = grade;
            selectedAdvancedSubject = null; // 선택 초기화
            
            // 버튼 활성화
            document.querySelectorAll('.grade-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 과목 표시
            showSubjects(grade);
        }
        
        // 챌린지 모드 수정
        function navigateToChallenge() {
            if (selectedAdvancedSubject === null) {
                alert('먼저 과목을 선택해주세요!');
                // 과목 선택 영역으로 스크롤
                document.getElementById('subject-grid').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
                return;
            }
            
            // 기존 챌린지 로직
            alert('심화학습 챌린지를 시작합니다!');
        }
        

        // 진행상황 저장/불러오기
        function loadProgress() {
            const saved = localStorage.getItem('advancedProgress');
            if (saved) {
                const data = JSON.parse(saved);
                score = data.score || 0;
                streak = data.streak || 0;
                
                document.getElementById('score-display').textContent = score + '점';
                document.getElementById('streak-display').textContent = streak + '일 연속';
                document.getElementById('my-best-score').textContent = data.bestScore || 0;
            }
            
            // 오늘 날짜 확인
            const today = new Date().toDateString();
            const lastAccess = localStorage.getItem('lastAccess');
            
            if (lastAccess === today) {
                const attempts = localStorage.getItem('todayAttempts') || '0';
                document.getElementById('today-attempts').textContent = attempts;
            } else {
                localStorage.setItem('lastAccess', today);
                localStorage.setItem('todayAttempts', '0');
                
                // 연속 학습 체크
                const yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);
                
                if (lastAccess === yesterday.toDateString()) {
                    streak++;
                } else {
                    streak = 1;
                }
                
                document.getElementById('streak-display').textContent = streak + '일 연속';
                saveProgress();
            }
            
            // 초기 과목 표시
            showSubjects(currentGrade);
        }

        function saveProgress() {
            localStorage.setItem('advancedProgress', JSON.stringify({
                score: score,
                streak: streak,
                bestScore: localStorage.getItem('bestScore') || 0,
                lastAccess: new Date().toDateString()
            }));
        }
        
        // 초기화
        window.onload = function() {
            startTimer();
            loadProgress();
            
            // 마지막 선택 복원
            <?php if ($should_restore && $last_selection): ?>
            const lastData = <?php echo json_encode(json_decode($last_selection->selection_data, true)); ?>;
            if (lastData && lastData.grade) {
                currentGrade = lastData.grade;
                // 학년 버튼 활성화
                document.querySelectorAll('.grade-button').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.textContent.toLowerCase().includes(lastData.grade)) {
                        btn.classList.add('active');
                    }
                });
                
                // 난이도 설정
                if (lastData.difficulty) {
                    selectDifficulty(lastData.difficulty);
                }
                
                showSubjects(lastData.grade);
                
                <?php if ($direct_to_study): ?>
                // 직접 도전 과제로 이동
                setTimeout(() => {
                    const subjectCards = document.querySelectorAll('.subject-card');
                    subjectCards.forEach((card, index) => {
                        if (card.textContent.includes('<?php echo $last_selection->last_unit; ?>')) {
                            card.click();
                            // 챌린지 버튼 자동 클릭
                            setTimeout(() => {
                                navigateToChallenge();
                            }, 1000);
                        }
                    });
                }, 800);
                <?php else: ?>
                // 과목이 선택되었던 경우 하이라이트
                setTimeout(() => {
                    highlightLastSelection('.subject-card', '<?php echo $last_selection->last_unit; ?>');
                }, 500);
                <?php endif; ?>
            }
            <?php endif; ?>
        };
    </script>
    </div> <!-- main-content-wrapper 닫기 -->
</body>
</html>