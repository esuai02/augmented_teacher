<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userid = $_GET["userid"] ?? $USER->id; 

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$userid' AND fieldid='22'"); 
$role = $userrole->data ?? 'student';
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-role" content="<?php echo htmlspecialchars($role); ?>">
    <title>🌌 Shining Stars - 인터랙티브 수학 성찰 여정</title>
    
    <!-- CSS 로드 -->
    <link rel="stylesheet" href="css/bias-detection-system.css">
    <link rel="stylesheet" href="css/interactive-journey.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0e27;
            color: #e0e0e0;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* 배경 효과 */
        .cosmos-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at center, #1a1e3a 0%, #0a0e27 100%);
            z-index: -2;
        }

        .stars {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }

        /* 메인 컨테이너 */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }

        /* 헤더 */
        header {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            animation: fadeInDown 1s ease-out;
        }

        h1 {
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #888;
            font-size: 1.1em;
        }

        /* 메인 콘텐츠 영역 */
        .content-area {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            flex: 1;
        }

        /* 아바타 섹션 */
        .avatar-section {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 20px;
            height: fit-content;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeInLeft 1s ease-out;
        }

        #avatar {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4em;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            position: relative;
        }

        #avatar::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: inherit;
            filter: blur(20px);
            opacity: 0.5;
            z-index: -1;
        }

        #avatar:hover {
            transform: scale(1.1) rotate(10deg);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        #avatarSpeech {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            line-height: 1.6;
            font-size: 0.95em;
            position: relative;
            animation: slideIn 0.5s ease-out;
        }

        #avatarSpeech::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid rgba(255, 255, 255, 0.1);
        }

        /* 스탯 표시 */
        .avatar-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.8em;
            color: #888;
            margin-top: 5px;
        }

        /* 여정 맵 */
        .journey-map {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            animation: fadeInRight 1s ease-out;
        }

        #constellation {
            position: relative;
            height: 500px;
            margin-bottom: 30px;
        }

        .constellation-node {
            position: absolute;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.8), rgba(102, 126, 234, 0.2));
            border: 2px solid #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .constellation-node:hover {
            transform: scale(1.2);
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.8);
        }

        .constellation-node.completed {
            background: radial-gradient(circle, rgba(16, 185, 129, 0.8), rgba(16, 185, 129, 0.2));
            border-color: #10b981;
        }

        .constellation-node.locked {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            opacity: 0.5;
            cursor: not-allowed;
        }

        .constellation-node.active {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            50% {
                box-shadow: 0 0 0 20px rgba(102, 126, 234, 0);
            }
        }

        .node-icon {
            font-size: 1.5em;
        }

        .node-line {
            position: absolute;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.5), transparent);
            transform-origin: left center;
            z-index: 1;
        }

        .node-line.active {
            background: linear-gradient(90deg, transparent, #667eea, transparent);
            animation: flowLine 2s linear infinite;
        }

        @keyframes flowLine {
            0% { background-position: -100px; }
            100% { background-position: 100px; }
        }

        /* 콘텐츠 패널 */
        #contentPanel {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 30px;
            min-height: 300px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        /* 진행 타이머 */
        #progressTimer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 15px 25px;
            backdrop-filter: blur(10px);
            display: none;
        }

        #timerDisplay {
            font-size: 1.2em;
            font-weight: bold;
            color: #667eea;
        }

        /* 툴바 */
        .toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .tool-btn {
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .tool-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* 애니메이션 */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 반응형 */
        @media (max-width: 1024px) {
            .content-area {
                grid-template-columns: 1fr;
            }

            .avatar-section {
                display: flex;
                align-items: center;
                gap: 20px;
            }

            #avatar {
                width: 100px;
                height: 100px;
                font-size: 3em;
            }

            .avatar-stats {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2em;
            }

            .toolbar {
                top: 10px;
                right: 10px;
            }

            .tool-btn {
                padding: 8px 12px;
                font-size: 0.9em;
            }

            #contentPanel {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- 배경 효과 -->
    <div class="cosmos-bg"></div>
    <div class="stars" id="stars"></div>

    <!-- 툴바 -->
    <div class="toolbar">
        <button class="tool-btn" onclick="window.updatedNavigationMap?.open()">
            🗺️ 우주 지도
        </button>
        <button class="tool-btn" onclick="window.biasCardLibrary?.open()">
            🃏 카드 도감
        </button>
        <button class="tool-btn" onclick="showHelp()">
            ❓ 도움말
        </button>
    </div>

    <!-- 메인 컨테이너 -->
    <div class="main-container">
        <header>
            <h1>🌌 Shining Stars</h1>
            <p class="subtitle">수학적 사고의 우주를 탐험하며 인지편향을 극복하는 여정</p>
        </header>

        <div class="content-area">
            <!-- 아바타 섹션 -->
            <div class="avatar-section">
                <div id="avatar" onclick="journey.shakeAvatar()">🌟</div>
                <div id="avatarSpeech">안녕! 오늘은 어떤 수학적 발견을 할까요? 🌟</div>
                
                <div class="avatar-stats">
                    <div class="stat-item">
                        <div class="stat-value" id="completedCount">0</div>
                        <div class="stat-label">완료</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="unlockedCount">1</div>
                        <div class="stat-label">열림</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="cardsCount">0</div>
                        <div class="stat-label">카드</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="streakCount">0</div>
                        <div class="stat-label">연속</div>
                    </div>
                </div>
            </div>

            <!-- 여정 맵 -->
            <div class="journey-map">
                <div id="constellation"></div>
                <div id="contentPanel">
                    <div style="text-align: center; color: #888;">
                        <p style="font-size: 1.2em; margin-bottom: 10px;">✨ 별을 클릭하여 여정을 시작하세요</p>
                        <p>각 노드는 수학적 사고의 한 측면을 나타냅니다</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 진행 타이머 -->
    <div id="progressTimer">
        <div>다음 노드까지</div>
        <div id="timerDisplay">00:00:00</div>
    </div>

    <!-- JavaScript 로드 -->
    <script>
        // PHP 변수를 JavaScript로 전달
        window.userId = <?php echo json_encode($userid); ?>;
        window.userRole = <?php echo json_encode($role); ?>;
    </script>

    <!-- 시스템 스크립트 -->
    <script src="js/MathematicalThinkingFramework.js"></script>
    <script src="js/UpdatedNavigationMap.js"></script>
    <script src="js/InteractiveJourney.js"></script>
    
    <script>
        // 질문 데이터
        window.questions = {
            0: {
                title: "수학 여정의 시작",
                text: "오늘 수학을 공부하면서 가장 기억에 남는 순간은 무엇이었나요? 그 순간의 감정과 생각을 자유롭게 표현해보세요.",
                type: "reflection"
            },
            1: {
                title: "계산과의 만남",
                text: "복잡한 계산을 마주했을 때, 당신만의 접근 방법은 무엇인가요? 최근 해결한 계산 문제를 예로 들어 설명해주세요.",
                type: "calculation"
            },
            2: {
                title: "도형의 세계",
                text: "도형을 볼 때 떠오르는 이미지나 패턴이 있나요? 오늘 배운 도형 개념을 일상생활과 연결해보세요.",
                type: "geometry"
            },
            3: {
                title: "연산의 깊이",
                text: "덧셈, 뺄셈, 곱셈, 나눗셈 중 가장 어려운 것은 무엇이며, 그 이유는 무엇인가요?",
                type: "operation"
            },
            4: {
                title: "문제 해결 전략",
                text: "막막한 문제를 만났을 때, 첫 번째로 하는 행동은 무엇인가요? 그 방법이 효과적이었던 경험을 공유해주세요.",
                type: "strategy"
            },
            5: {
                title: "패턴의 발견",
                text: "수학에서 발견한 흥미로운 패턴이나 규칙이 있나요? 그것을 어떻게 발견했는지 과정을 설명해주세요.",
                type: "pattern"
            },
            6: {
                title: "깨달음의 순간",
                text: "갑자기 '아하!'하고 이해가 된 순간이 있었나요? 그 깨달음이 어떻게 왔는지 묘사해보세요.",
                type: "insight"
            },
            7: {
                title: "미래 예측",
                text: "지금 배우는 수학이 미래에 어떻게 도움이 될 것 같나요? 구체적인 상황을 상상해보세요.",
                type: "prediction"
            },
            8: {
                title: "여정의 정점",
                text: "이 여정을 통해 수학에 대한 생각이 어떻게 변했나요? 처음과 지금을 비교해보세요.",
                type: "mastery"
            }
        };

        // 별자리 노드 생성
        function createConstellation() {
            const container = document.getElementById('constellation');
            container.innerHTML = '';
            
            // 노드 위치 정의 (원형 배치)
            const centerX = container.offsetWidth / 2;
            const centerY = container.offsetHeight / 2;
            const radius = 150;
            
            const nodePositions = [];
            for (let i = 0; i < 9; i++) {
                const angle = (i * 40 - 90) * Math.PI / 180;
                const x = centerX + radius * Math.cos(angle);
                const y = centerY + radius * Math.sin(angle);
                nodePositions.push({ x, y });
            }
            
            // 연결선 그리기
            const connections = [
                [0, 1], [0, 2], [1, 3], [1, 4], [2, 4], [2, 5],
                [3, 5], [3, 6], [4, 6], [4, 7], [5, 7], [6, 8], [7, 8]
            ];
            
            connections.forEach(([from, to]) => {
                const line = document.createElement('div');
                line.className = 'node-line';
                line.id = `line-${from}-${to}`;
                
                const x1 = nodePositions[from].x;
                const y1 = nodePositions[from].y;
                const x2 = nodePositions[to].x;
                const y2 = nodePositions[to].y;
                
                const length = Math.sqrt((x2 - x1) ** 2 + (y2 - y1) ** 2);
                const angle = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;
                
                line.style.width = `${length}px`;
                line.style.left = `${x1}px`;
                line.style.top = `${y1}px`;
                line.style.transform = `rotate(${angle}deg)`;
                
                container.appendChild(line);
            });
            
            // 노드 생성
            const icons = ['🌟', '🔢', '📐', '➕', '🎯', '🔄', '💡', '🔮', '👑'];
            
            nodePositions.forEach((pos, i) => {
                const node = document.createElement('div');
                node.className = 'constellation-node';
                node.id = `node-${i}`;
                node.setAttribute('data-node-id', i);
                node.style.left = `${pos.x - 30}px`;
                node.style.top = `${pos.y - 30}px`;
                
                // 초기 상태 설정
                if (i === 0) {
                    node.classList.add('unlocked');
                } else {
                    node.classList.add('locked');
                }
                
                // 진행 상황에 따라 상태 업데이트
                if (journey?.journeyState.completedNodes.has(i)) {
                    node.classList.add('completed');
                    node.classList.remove('locked');
                }
                if (journey?.journeyState.unlockedNodes.has(i)) {
                    node.classList.remove('locked');
                    node.classList.add('unlocked');
                }
                
                const icon = document.createElement('div');
                icon.className = 'node-icon';
                icon.textContent = icons[i];
                node.appendChild(icon);
                
                node.onclick = () => journey.selectNode(i);
                
                container.appendChild(node);
            });
            
            updateStats();
        }

        // 통계 업데이트
        function updateStats() {
            if (!window.journey) return;
            
            document.getElementById('completedCount').textContent = 
                journey.journeyState.completedNodes.size;
            document.getElementById('unlockedCount').textContent = 
                journey.journeyState.unlockedNodes.size;
            document.getElementById('cardsCount').textContent = 
                journey.journeyState.collectedCards?.size || 0;
            document.getElementById('streakCount').textContent = 
                journey.journeyState.currentStreak || 0;
        }

        // 별 배경 생성
        function createStars() {
            const starsContainer = document.getElementById('stars');
            for (let i = 0; i < 100; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 3 + 's';
                starsContainer.appendChild(star);
            }
        }

        // 도움말 표시
        function showHelp() {
            const helpText = `
🌟 Shining Stars 사용 가이드

1. 별을 클릭하여 질문을 확인하세요
2. 자유롭게 생각을 작성하세요
3. AI 피드백을 받고 성장하세요
4. 새로운 노드가 열리면 계속 탐험하세요

💡 팁:
- 구체적인 예시를 들어보세요
- 감정도 함께 표현해보세요
- 편향 경고에 주의하세요

${window.userRole === 'teacher' ? '👩‍🏫 선생님 모드: 대기 시간 없이 진행 가능' : ''}
            `;
            
            alert(helpText);
        }

        // 초기화
        window.addEventListener('DOMContentLoaded', () => {
            createStars();
            createConstellation();
            
            // 5초마다 통계 업데이트
            setInterval(updateStats, 5000);
            
            console.log('🌟 Shining Stars 시스템 준비 완료');
            console.log(`사용자 역할: ${window.userRole}`);
        });
    </script>
</body>
</html>