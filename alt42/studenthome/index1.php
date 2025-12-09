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
    <title>개념학습 - Math Learning Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            overflow-x: hidden;
            color: #333;
            display: flex;
        }

        /* 페이지 특정 스타일은 컴포넌트에서 로드됨 */

        /* 네비게이션 바 (기존 스타일 오버라이드) */
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
            background: #4facfe;
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
            background: #00a8ff;
            transform: translateY(-2px);
        }

        .timer-display {
            font-family: monospace;
            color: #333;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            background: linear-gradient(135deg, #4facfe, #00f2fe);
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

        /* 기초 카드 */
        .basic-card {
            --gradient-start: #667eea;
            --gradient-end: #764ba2;
        }

        /* 개념원리 카드 */
        .principle-card {
            --gradient-start: #f093fb;
            --gradient-end: #f5576c;
        }

        /* 응용 카드 */
        .application-card {
            --gradient-start: #4facfe;
            --gradient-end: #00f2fe;
        }

        /* AI 학습 버튼 */
        .ai-button {
            width: 100%;
            max-width: 965px;
            padding: 1.5rem;
            background: linear-gradient(135deg, #fa709a, #fee140);
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

        .ai-button:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        /* 레벨 1 - 세부 선택 */
        .level-1 {
            display: none;
        }

        .subject-title {
            text-align: center;
            color: white;
            font-size: 3rem;
            margin-bottom: 3rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .content-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .content-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 3px solid transparent;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-color: #4facfe;
        }

        .content-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .content-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .content-card p {
            color: #666;
            font-size: 0.95rem;
        }

        /* 레벨 2 - AI 학습 모드 */
        .level-2 {
            display: none;
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .ai-header {
            background: linear-gradient(135deg, #fa709a, #fee140);
            padding: 2rem;
            border-radius: 1rem;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .ai-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            min-height: 600px;
        }

        /* 학습 영역 */
        .learning-area {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 2rem;
        }

        .concept-box {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .concept-box h3 {
            color: #4facfe;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .example-box {
            background: #e3f2fd;
            border-left: 4px solid #4facfe;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .practice-area {
            margin-top: 2rem;
        }

        .practice-question {
            background: #fff3e0;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .answer-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.5rem;
            font-size: 1rem;
            margin-top: 1rem;
        }

        .check-button {
            padding: 0.75rem 2rem;
            background: #4facfe;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            margin-top: 1rem;
            font-size: 1rem;
            font-weight: bold;
        }

        .check-button:hover {
            background: #00a8ff;
        }

        /* AI 튜터 영역 */
        .ai-tutor-area {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .tutor-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #fa709a, #fee140);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            min-height: 300px;
        }

        .chat-message {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            max-width: 85%;
        }

        .chat-message.ai {
            background: #e3f2fd;
            margin-right: auto;
        }

        .chat-message.user {
            background: #f3e5f5;
            margin-left: auto;
            text-align: right;
        }

        .chat-input-group {
            display: flex;
            gap: 0.5rem;
        }

        .chat-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.5rem;
            font-size: 0.95rem;
        }

        .chat-send {
            padding: 0.75rem 1.5rem;
            background: #fa709a;
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: bold;
        }

        /* 진행 상태 */
        .progress-bar {
            margin: 2rem 0;
            background: #e0e0e0;
            border-radius: 1rem;
            height: 1.5rem;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            height: 100%;
            width: 0%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            font-weight: bold;
        }

        /* 미니맵 */
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
            background: #4facfe;
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
            border-color: #4facfe;
            color: #4facfe;
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
            border-color: #4facfe;
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
            border-color: #4facfe;
            background: #f0f8ff;
            position: relative;
        }
        
        .subject-card.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 15px;
            background: #4facfe;
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

        /* 학년 탭 */
        .grade-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .grade-tab {
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
        
        .grade-tab:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .grade-tab.active {
            background: white;
            border-color: #4facfe;
            color: #4facfe;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* 과목 영역 */
        .subjects-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        /* 과목 선택 컨테이너 */
        .subject-selection-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .subjects-title {
            text-align: center;
            font-size: 2rem;
            color: #333;
            margin-bottom: 2rem;
            font-weight: 700;
        }
        
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
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
            border-color: #4facfe;
        }
        
        .subject-card.selected {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            border-color: transparent;
            position: relative;
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
        
        .subject-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .subject-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .subject-desc {
            font-size: 0.9rem;
            color: #666;
        }

        /* 반응형 */
        @media (max-width: 1024px) {
            .main-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .ai-content {
                grid-template-columns: 1fr;
            }
            
            .ai-tutor-area {
                max-height: 400px;
            }
            
            .grade-tabs {
                flex-direction: column;
                align-items: center;
            }
            
            .subjects-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-cards {
                grid-template-columns: 1fr;
            }
            
            .welcome-title {
                font-size: 2.5rem;
            }
            
            .subjects-grid {
                grid-template-columns: 1fr;
            }
            
            .grade-tab {
                padding: 0.8rem 1.5rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php
    // 에이전트 휠 메뉴 포함
    include 'includes/agent_wheel.php';
    ?>

    <!-- 메인 컨텐츠 래퍼 -->
    <div class="main-wrapper">
        <?php
        // 헤더 컴포넌트 포함
        $active_page = 'index1';
        include 'includes/header.php';
        ?>


    <!-- 메인 컨테이너 -->
    <div class="main-container" id="main-container">
        <!-- 레벨 0: 메인 대시보드 -->
        <div class="level-0" id="level-0">
            <h1 class="welcome-title">개념학습 센터</h1>
            <p class="welcome-subtitle">기초부터 탄탄하게, 개념을 완벽하게 마스터하세요</p>
            
            <!-- 학년별 선택 -->
            <div class="grade-selector">
                <button class="grade-button active" onclick="selectGrade('elementary')">초등수학</button>
                <button class="grade-button" onclick="selectGrade('middle')">중등수학</button>
                <button class="grade-button" onclick="selectGrade('high')">고등수학</button>
            </div>
            
            <!-- 과목 선택 -->
            <div class="subject-selection-container">
                <h2 class="subjects-title" id="subjects-title">초등수학 과목을 선택하세요</h2>
                <div class="subject-grid" id="subject-grid">
                    <!-- 동적으로 생성됨 -->
                </div>
            </div>
            
        </div>

    </div>


    <script>
        // 전역 변수
        let studyTime = 0;
        let progress = 0;
        let currentGrade = 'elementary';
        
        // 사용자 정보
        const studentId = <?php echo $studentid; ?>;
        const pageType = 'index1';
        let lastSelectedUnit = '';
        let lastSelectedTopic = '';

        
        // 학년별 과목 데이터
        const subjectsData = {
            elementary: {
                title: '초등수학 과목',
                subjects: [
                    { code: '4-1', name: '초등수학 4-1', desc: '초등학교 4학년 1학기' },
                    { code: '4-2', name: '초등수학 4-2', desc: '초등학교 4학년 2학기' },
                    { code: '5-1', name: '초등수학 5-1', desc: '초등학교 5학년 1학기' },
                    { code: '5-2', name: '초등수학 5-2', desc: '초등학교 5학년 2학기' },
                    { code: '6-1', name: '초등수학 6-1', desc: '초등학교 6학년 1학기' },
                    { code: '6-2', name: '초등수학 6-2', desc: '초등학교 6학년 2학기' }
                ]
            },
            middle: {
                title: '중등수학 과목',
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
                title: '고등수학 과목',
                subjects: [
                    { code: 'common1', name: '공통수학1', desc: '고등학교 공통 수학 1' },
                    { code: 'common2', name: '공통수학2', desc: '고등학교 공통 수학 2' },
                    { code: 'algebra', name: '대수', desc: '대수학과 방정식' },
                    { code: 'calculus1', name: '미적분 I', desc: '미분과 적분의 기초' },
                    { code: 'stats', name: '확률과 통계', desc: '확률과 통계의 기본' },
                    { code: 'calculus2', name: '미적분 II', desc: '미적분의 심화' },
                    { code: 'geometry', name: '기하', desc: '공간기하와 벡터' }
                ]
            }
        };

        // 초기화
        window.onload = function() {
            initAgentWheel(); // 에이전트 휠 초기화
            startTimer();
            loadSavedProgress();
            
            // 마지막 선택 복원
            <?php if ($should_restore && $last_selection): ?>
            const lastData = <?php echo json_encode(json_decode($last_selection->selection_data, true)); ?>;
            if (lastData && lastData.grade) {
                selectGrade(lastData.grade);
                
                // 직접 학습으로 이동
                <?php if ($direct_to_study): ?>
                setTimeout(() => {
                    // 마지막 선택한 과목 찾기
                    const subjectElements = document.querySelectorAll('.concept-subject-card');
                    subjectElements.forEach((el, index) => {
                        if (el.textContent.includes('<?php echo $last_selection->last_unit; ?>')) {
                            // 자동으로 클릭하여 학습 시작
                            el.click();
                            
                            // 추가 메시지
                            setTimeout(() => {
                                alert('이전에 학습하던 <?php echo $last_selection->last_unit; ?> 과목으로 이동합니다.');
                            }, 100);
                        }
                    });
                }, 800);
                <?php else: ?>
                // 과목이 선택되었던 경우 하이라이트
                setTimeout(() => {
                    const subjectElements = document.querySelectorAll('.concept-subject-card');
                    subjectElements.forEach((el, index) => {
                        if (el.textContent.includes('<?php echo $last_selection->last_unit; ?>')) {
                            el.classList.add('last-selected');
                            el.style.border = '2px solid #3b82f6';
                            el.style.boxShadow = '0 0 10px rgba(59, 130, 246, 0.3)';
                        }
                    });
                }, 500);
                <?php endif; ?>
            } else {
                showSubjects(currentGrade);
            }
            <?php else: ?>
            showSubjects(currentGrade);
            <?php endif; ?>
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


        // 진행도 업데이트
        function updateProgress() {
            progress = Math.min(progress + 10, 100);
            document.getElementById('progress-text').textContent = `${progress}% 완료`;
            document.getElementById('progress-fill').style.width = `${progress}%`;
            document.getElementById('progress-fill').textContent = `${progress}%`;
            saveProgress();
        }




        // 진행도 저장/불러오기
        function saveProgress() {
            localStorage.setItem('conceptProgress', JSON.stringify({
                progress: progress,
                studyTime: studyTime,
                lastAccess: new Date().toISOString()
            }));
        }

        function loadSavedProgress() {
            const saved = localStorage.getItem('conceptProgress');
            if (saved) {
                const data = JSON.parse(saved);
                progress = data.progress || 0;
                document.getElementById('progress-text').textContent = `${progress}% 완료`;
                document.getElementById('progress-fill').style.width = `${progress}%`;
                document.getElementById('progress-fill').textContent = `${progress}%`;
            }
        }

        // 학년 선택
        function selectGrade(grade) {
            currentGrade = grade;
            selectedConceptSubject = null; // 선택 초기화
            
            // 버튼 활성화
            document.querySelectorAll('.grade-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 제목 업데이트
            const gradeNames = {
                'elementary': '초등수학',
                'middle': '중등수학',
                'high': '고등수학'
            };
            document.getElementById('subjects-title').textContent = `${gradeNames[grade]} 과목을 선택하세요`;
            
            // 과목 표시
            showSubjects(grade);
        }
        
        // 과목 표시
        function showSubjects(grade) {
            const container = document.getElementById('subject-grid');
            const data = subjectsData[grade];
            
            container.innerHTML = data.subjects.map((subject, index) => `
                <div class="subject-card ${selectedConceptSubject === index ? 'selected' : ''}" id="concept-subject-${index}" onclick="selectConceptSubject('${grade}', '${subject.code}', '${subject.name}', ${index})">
                    <h3>${subject.name}</h3>
                    <p>${subject.desc}</p>
                </div>
            `).join('');
        }
        
        let selectedConceptSubject = null;
        
        // 선택 정보 저장 함수
        function saveSelection() {
            const data = {
                userid: studentId,
                page_type: pageType,
                last_path: currentGrade,
                last_unit: lastSelectedUnit,
                last_topic: lastSelectedTopic,
                selection_data: {
                    grade: currentGrade,
                    subject: selectedConceptSubject
                }
            };
            
            fetch('save_selection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    console.log('선택 저장 완료');
                }
            })
            .catch(error => {
                console.error('선택 저장 실패:', error);
            });
        }
        
        // 과목 선택
        function selectConceptSubject(grade, code, name, index) {
            // 이전 선택 해제
            if (selectedConceptSubject !== null) {
                document.getElementById(`concept-subject-${selectedConceptSubject}`).classList.remove('selected');
            }
            
            // 새 선택 적용
            selectedConceptSubject = index;
            document.getElementById(`concept-subject-${index}`).classList.add('selected');
            
            // 선택 정보 업데이트
            lastSelectedUnit = name;
            lastSelectedTopic = code;
            
            // 최근 학습 저장
            const recentCourse = {
                type: '개념학습',
                grade: grade,
                subject: name,
                code: code,
                timestamp: new Date().toISOString()
            };
            
            // 서버에 선택 정보 저장
            saveSelection();
            localStorage.setItem('recentCourse', JSON.stringify(recentCourse));
            
            // mathking.kr 링크로 이동
            let url = '';
            if (grade === 'elementary') {
                // 초등수학 링크
                const cidMap = {
                    '4-1': 73,
                    '4-2': 74,
                    '5-1': 75,
                    '5-2': 76,
                    '6-1': 78,
                    '6-2': 79
                };
                if (cidMap[code]) {
                    url = `https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?&cid=${cidMap[code]}&type=init`;
                }
            } else if (grade === 'middle') {
                // 중등수학 링크
                const cidMap = {
                    '1-1': 66,
                    '1-2': 67,
                    '2-1': 68,
                    '2-2': 69,
                    '3-1': 71,
                    '3-2': 72
                };
                if (cidMap[code]) {
                    url = `https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?&cid=${cidMap[code]}&type=init`;
                }
            } else if (grade === 'high') {
                // 고등수학 링크
                const cidMap = {
                    'common1': 106,
                    'common2': 107,
                    'algebra': 61,
                    'calculus1': 62,
                    'stats': 64,
                    'calculus2': 63,
                    'geometry': 65
                };
                if (cidMap[code]) {
                    url = `https://mathking.kr/moodle/local/augmented_teacher/books/chapter.php?&cid=${cidMap[code]}&type=init`;
                }
            }
            
            if (url) {
                // 현재 창에서 열기
                window.location.href = url;
            } else {
                alert(`${name} 과목이 선택되었습니다! 학습을 시작하세요.`);
            }
        }

    </script>
    </div> <!-- main-wrapper 닫기 -->
</body>
</html>