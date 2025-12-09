
<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
require_login();
$userid=$_GET["userid"]; 
 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='22'  "); 
$role=$userrole->data;

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>아바타 가이드 수학 성찰 여정</title>
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

        /* 아바타 섹션 */
        .avatar-section {
            position: fixed;
            left: 50px;
            top: 50%;
            transform: translateY(-50%);
            width: 200px;
            z-index: 10;
        }

        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }

        .avatar {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4em;
            animation: float 3s ease-in-out infinite;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .avatar:hover {
            transform: scale(1.1);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .avatar-glow {
            position: absolute;
            top: -20px;
            left: -20px;
            right: -20px;
            bottom: -20px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.1); }
        }

        .avatar-speech {
            background: #1e2139;
            border: 1px solid #2a2d4a;
            border-radius: 15px;
            padding: 15px;
            position: relative;
            margin-top: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            max-width: 250px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .avatar-speech::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid #2a2d4a;
        }

        /* 여정 맵 */
        .journey-map {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 250px;
            position: relative;
        }

        .constellation {
            position: relative;
            width: 800px;
            height: 600px;
        }

        /* 노드 시스템 */
        .journey-node {
            position: absolute;
            width: 80px;
            height: 80px;
            background: #1e2139;
            border: 2px solid #3a3d5a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .journey-node.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.5);
            transform: scale(1.2);
            z-index: 3;
        }

        .journey-node.completed {
            background: #2d3561;
            border-color: #4a5189;
        }

        .journey-node.locked {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .journey-node:hover:not(.locked) {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
        }

        .node-icon {
            font-size: 2em;
        }

        .node-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.9em;
            white-space: nowrap;
            color: #888;
        }

        /* 연결선 */
        .connection-line {
            position: absolute;
            height: 2px;
            background: linear-gradient(90deg, transparent, #3a3d5a, transparent);
            transform-origin: left center;
            z-index: 1;
        }

        .connection-line.active {
            background: linear-gradient(90deg, transparent, #667eea, transparent);
            animation: flowLine 2s linear infinite;
        }

        @keyframes flowLine {
            from { background-position: -100px; }
            to { background-position: 100px; }
        }

        /* 콘텐츠 패널 */
        .content-panel {
            position: fixed;
            right: -600px;
            top: 0;
            width: 600px;
            height: 100vh;
            background: #1a1e3a;
            border-left: 1px solid #2a2d4a;
            transition: right 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            z-index: 20;
        }

        .content-panel.open {
            right: 0;
            box-shadow: -10px 0 30px rgba(0,0,0,0.5);
        }

        .panel-header {
            padding: 30px;
            background: linear-gradient(135deg, #1e2139 0%, #2a2d4a 100%);
            border-bottom: 1px solid #3a3d5a;
        }

        .close-panel {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: transparent;
            border: 1px solid #4a5189;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-panel:hover {
            background: #2a2d4a;
            transform: rotate(90deg);
        }

        .panel-content {
            padding: 30px;
        }

        .question-card {
            background: #0f1228;
            border: 1px solid #2a2d4a;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }

        .question-text {
            font-size: 1.2em;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #e0e0e0;
        }

        .answer-area {
            width: 100%;
            min-height: 150px;
            background: #1a1e3a;
            border: 1px solid #3a3d5a;
            border-radius: 10px;
            padding: 15px;
            color: #e0e0e0;
            font-size: 1em;
            resize: vertical;
            transition: border-color 0.3s ease;
        }

        .answer-area:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            display: block;
            margin-left: auto;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        /* 피드백 카드 */
        .feedback-container {
            margin-top: 30px;
            display: grid;
            gap: 20px;
        }

        .feedback-card {
            background: linear-gradient(135deg, #1e2139 0%, #2a2d4a 100%);
            border: 1px solid #3a3d5a;
            border-radius: 15px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.5s ease-out;
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

        .feedback-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        .feedback-card.insight::before {
            background: linear-gradient(180deg, #f093fb 0%, #f5576c 100%);
        }

        .feedback-title {
            font-size: 0.9em;
            color: #888;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .feedback-text {
            font-size: 1.1em;
            line-height: 1.5;
        }

        /* 진행 타이머 */
        .progress-timer {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e2139;
            border: 1px solid #3a3d5a;
            border-radius: 15px;
            padding: 20px;
            min-width: 250px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .progress-timer.clickable {
            cursor: pointer;
        }
        
        .progress-timer.clickable:hover {
            background: #252847;
            border-color: #4a5189;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }

        .timer-display {
            font-size: 1.5em;
            text-align: center;
            color: #667eea;
            font-weight: bold;
            margin-top: 10px;
        }

        /* 모바일 반응형 */
        @media (max-width: 1024px) {
            .avatar-section {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                top: auto;
                width: auto;
                display: flex;
                align-items: center;
                gap: 20px;
            }

            .avatar-container {
                width: 80px;
                height: 80px;
                margin: 0;
            }

            .avatar {
                font-size: 2em;
            }

            .avatar-speech {
                max-width: 200px;
                margin: 0;
            }

            .journey-map {
                margin-left: 0;
                margin-bottom: 150px;
            }

            .constellation {
                width: 100%;
                height: 500px;
            }

            .content-panel {
                width: 100%;
                right: -100%;
            }
        }
    </style>
</head>
<body>
    <div class="cosmos-bg"></div>
    <div class="stars" id="stars"></div>

    <div class="main-container">
        <header>
            <h1>수학 성찰의 별자리</h1>
            <p class="subtitle">당신만의 학습 여정을 탐험하세요</p>
        </header>

        <!-- 아바타 가이드 -->
        <div class="avatar-section">
            <div class="avatar-container">
                <div class="avatar-glow"></div>
                <div class="avatar" id="avatar">🌟</div>
            </div>
            <div class="avatar-speech" id="avatarSpeech">
                안녕! 나는 네 학습 여정의 동반자야. 함께 수학의 우주를 탐험해보자! ✨
            </div>
        </div>

        <!-- 여정 맵 -->
        <div class="journey-map">
            <div class="constellation" id="constellation">
                <!-- 노드들이 동적으로 생성됩니다 -->
            </div>
        </div>
    </div>

    <!-- 콘텐츠 패널 -->
    <div class="content-panel" id="contentPanel">
        <div class="panel-header">
            <button class="close-panel" onclick="closePanel()">✕</button>
            <h2 id="questionTitle">질문 제목</h2>
        </div>
        <div class="panel-content" id="panelContent">
            <!-- 질문 콘텐츠가 여기에 로드됩니다 -->
        </div>
    </div>

    <!-- 진행 타이머 -->
    <div class="progress-timer" id="progressTimer" style="display: none;">
        <div id="timerTitle">다음 별까지 남은 시간</div>
        <div class="timer-display" id="timerDisplay">00:00:00</div>
        <div id="timerHint" style="display: none; font-size: 0.85em; color: #888; margin-top: 10px; cursor: pointer;">
            ⚡ 클릭하여 즉시 진행
        </div>
    </div>

    <script>
        // 상태 관리
        let currentNode = null;
        let completedNodes = new Set();
        let unlockedNodes = new Set([0]); // 첫 번째 노드만 열림
        let lastAnswerTime = null;
        let timerInterval = null;

        // 아바타 메시지
        const avatarMessages = {
            welcome: "안녕! 나는 네 학습 여정의 동반자야. 함께 수학의 우주를 탐험해보자! ✨",
            nodeComplete: "훌륭해! 새로운 별을 밝혔어! 다음 여정을 위해 잠시 쉬어가자. 🌙",
            pathChoice: "이제 네가 선택할 차례야. 어느 길로 갈까? 🛤️",
            locked: "아직은 이 별에 닿을 수 없어. 시간이 더 필요해! ⏳",
            encouragement: [
                "네 생각이 별처럼 빛나고 있어! ⭐",
                "수학의 우주에서 길을 찾아가는 너의 모습이 멋져! 🚀",
                "어려움도 성장의 일부야. 계속 나아가자! 💪"
            ]
        };

        // 노드 구조 (비선형 경로)
        const nodeStructure = [
            { id: 0, x: 400, y: 300, icon: "🌱", label: "시작점", connections: [1, 2] },
            { id: 1, x: 250, y: 200, icon: "🔢", label: "계산의 길", connections: [3, 4] },
            { id: 2, x: 550, y: 200, icon: "📐", label: "도형의 길", connections: [4, 5] },
            { id: 3, x: 150, y: 100, icon: "🧮", label: "연산 탐구", connections: [6] },
            { id: 4, x: 400, y: 100, icon: "🎯", label: "문제 해결", connections: [6, 7] },
            { id: 5, x: 650, y: 100, icon: "📊", label: "패턴 발견", connections: [7] },
            { id: 6, x: 250, y: 50, icon: "💡", label: "통찰의 순간", connections: [8] },
            { id: 7, x: 550, y: 50, icon: "🔮", label: "예측과 추론", connections: [8] },
            { id: 8, x: 400, y: 20, icon: "⭐", label: "마스터리", connections: [] }
        ];

        // 질문 데이터
        const questions = {
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

        // 초기화
        function init() {
            createStars();
            createConstellation();
            updateAvatarMessage('welcome');
            
            // 저장된 진행 상황 불러오기
            loadProgress();
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

        // 별자리 맵 생성
        function createConstellation() {
            const constellation = document.getElementById('constellation');
            
            // 연결선 생성
            nodeStructure.forEach(node => {
                node.connections.forEach(targetId => {
                    const target = nodeStructure.find(n => n.id === targetId);
                    if (target) {
                        const line = createConnectionLine(node, target);
                        constellation.appendChild(line);
                    }
                });
            });
            
            // 노드 생성
            nodeStructure.forEach(node => {
                const nodeElement = createNode(node);
                constellation.appendChild(nodeElement);
            });
        }

        // 노드 생성
        function createNode(nodeData) {
            const node = document.createElement('div');
            node.className = 'journey-node';
            node.id = `node-${nodeData.id}`;
            node.style.left = nodeData.x + 'px';
            node.style.top = nodeData.y + 'px';
            
            if (completedNodes.has(nodeData.id)) {
                node.classList.add('completed');
            } else if (!unlockedNodes.has(nodeData.id)) {
                node.classList.add('locked');
            }
            
            node.innerHTML = `
                <span class="node-icon">${nodeData.icon}</span>
                <span class="node-label">${nodeData.label}</span>
            `;
            
            node.addEventListener('click', () => selectNode(nodeData.id));
            
            return node;
        }

        // 연결선 생성
        function createConnectionLine(from, to) {
            const line = document.createElement('div');
            line.className = 'connection-line';
            line.id = `line-${from.id}-${to.id}`;
            
            const dx = to.x - from.x;
            const dy = to.y - from.y;
            const length = Math.sqrt(dx * dx + dy * dy);
            const angle = Math.atan2(dy, dx) * 180 / Math.PI;
            
            line.style.width = length + 'px';
            line.style.left = (from.x + 40) + 'px';
            line.style.top = (from.y + 40) + 'px';
            line.style.transform = `rotate(${angle}deg)`;
            
            if (completedNodes.has(from.id) && unlockedNodes.has(to.id)) {
                line.classList.add('active');
            }
            
            return line;
        }

        // 노드 선택
        function selectNode(nodeId) {
            const node = nodeStructure.find(n => n.id === nodeId);
            
            if (!unlockedNodes.has(nodeId)) {
                updateAvatarMessage('locked');
                shakeAvatar();
                return;
            }
            
            if (completedNodes.has(nodeId)) {
                // 완료된 노드 - 답변 보기만 가능
                showCompletedQuestion(nodeId);
                return;
            }
            
            currentNode = nodeId;
            
            // 활성 노드 표시
            document.querySelectorAll('.journey-node').forEach(n => {
                n.classList.remove('active');
            });
            document.getElementById(`node-${nodeId}`).classList.add('active');
            
            // 질문 패널 열기
            openPanel(nodeId);
        }

        // 패널 열기
        function openPanel(nodeId) {
            const panel = document.getElementById('contentPanel');
            const question = questions[nodeId];
            
            document.getElementById('questionTitle').textContent = question.title;
            
            const content = `
                <div class="question-card">
                    <p class="question-text">${question.text}</p>
                    <textarea class="answer-area" id="answerInput" placeholder="여기에 당신의 생각을 적어주세요..."></textarea>
                    <button class="submit-btn" onclick="submitAnswer()">별빛 전송 ✨</button>
                </div>
                <div class="feedback-container" id="feedbackContainer"></div>
            `;
            
            document.getElementById('panelContent').innerHTML = content;
            panel.classList.add('open');
        }

        // 패널 닫기
        function closePanel() {
            document.getElementById('contentPanel').classList.remove('open');
        }

        // 답변 제출
        function submitAnswer() {
            const answer = document.getElementById('answerInput').value;
            if (!answer.trim()) {
                alert('답변을 입력해주세요!');
                return;
            }
            
            // 답변 저장
            saveAnswer(currentNode, answer);
            
            // 노드 완료 처리
            const completedNodeId = currentNode;
            completedNodes.add(completedNodeId);
            document.getElementById(`node-${completedNodeId}`).classList.add('completed');
            
            // 새로운 경로 열기
            unlockNextPaths(completedNodeId);
            
            // AI 피드백 생성
            generateFeedback(answer);
            
            // 타이머 시작 - currentNode를 완료된 노드로 유지
            currentNode = completedNodeId;
            startTimer();
            
            // 아바타 메시지 업데이트
            updateAvatarMessage('nodeComplete');
        }

        // AI 피드백 생성 (시뮬레이션)
        function generateFeedback(answer) {
            const feedbackContainer = document.getElementById('feedbackContainer');
            
            // 재미있는 피드백
            const funFeedback = {
                title: "재미있는 피드백",
                icon: "🤖",
                text: avatarMessages.encouragement[Math.floor(Math.random() * avatarMessages.encouragement.length)]
            };
            
            // 통찰 피드백
            const insightFeedback = {
                title: "예측형 통찰",
                icon: "🔮",
                text: "당신의 사고 과정에서 체계적인 접근 방식이 보여요. 이런 패턴은 복잡한 문제 해결에 큰 도움이 될 거예요!"
            };
            
            feedbackContainer.innerHTML = `
                <div class="feedback-card">
                    <div class="feedback-title">
                        <span>${funFeedback.icon}</span>
                        <span>${funFeedback.title}</span>
                    </div>
                    <p class="feedback-text">${funFeedback.text}</p>
                </div>
                <div class="feedback-card insight">
                    <div class="feedback-title">
                        <span>${insightFeedback.icon}</span>
                        <span>${insightFeedback.title}</span>
                    </div>
                    <p class="feedback-text">${insightFeedback.text}</p>
                </div>
            `;
            
            // 제출 버튼 비활성화
            document.querySelector('.submit-btn').disabled = true;
            document.querySelector('.submit-btn').textContent = '전송 완료 ✓';
        }

        // 다음 경로 열기
        function unlockNextPaths(nodeId) {
            const node = nodeStructure.find(n => n.id === nodeId);
            
            // PHP에서 전달된 role 확인
            const userRole = '<?php echo $role; ?>';
            const waitTime = userRole === 'student' ? 3600000 : 0; // student면 1시간, 아니면 즉시
            
            // 대기 시간 후에 연결된 노드들 열기
            setTimeout(() => {
                node.connections.forEach(connId => {
                    if (!completedNodes.has(connId)) {
                        unlockedNodes.add(connId);
                        document.getElementById(`node-${connId}`).classList.remove('locked');
                        
                        // 연결선 활성화
                        const line = document.getElementById(`line-${nodeId}-${connId}`);
                        if (line) line.classList.add('active');
                    }
                });
                
                // 경로 선택 메시지
                if (node.connections.length > 1) {
                    updateAvatarMessage('pathChoice');
                }
            }, waitTime);
            
            // 즉시 시각적 피드백
            lastAnswerTime = Date.now();
        }

        // 연결된 노드 즉시 열기
        function unlockConnectedNodes(nodeId) {
            const node = nodeStructure.find(n => n.id === nodeId);
            if (!node) return;
            
            node.connections.forEach(connId => {
                if (!completedNodes.has(connId)) {
                    unlockedNodes.add(connId);
                    document.getElementById(`node-${connId}`).classList.remove('locked');
                    
                    // 연결선 활성화
                    const line = document.getElementById(`line-${nodeId}-${connId}`);
                    if (line) line.classList.add('active');
                }
            });
            
            // 경로 선택 메시지
            if (node.connections.length > 1) {
                updateAvatarMessage('pathChoice');
            }
            
            // 타이머 숨기기
            document.getElementById('progressTimer').style.display = 'none';
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
        }

        // 타이머 시작
        function startTimer() {
            const userRole = '<?php echo $role; ?>';
            const timerContainer = document.getElementById('progressTimer');
            const timerHint = document.getElementById('timerHint');
            
            timerContainer.style.display = 'block';
            
            // student가 아니면 클릭 가능하게 설정
            if (userRole !== 'student') {
                timerContainer.classList.add('clickable');
                timerHint.style.display = 'block';
                
                // 클릭 이벤트 추가
                timerContainer.onclick = function() {
                    if (confirm('대기 시간을 건너뛰고 다음 별로 이동하시겠습니까?')) {
                        unlockConnectedNodes(currentNode);
                    }
                };
            } else {
                timerContainer.classList.remove('clickable');
                timerHint.style.display = 'none';
                timerContainer.onclick = null;
            }
            
            if (timerInterval) clearInterval(timerInterval);
            
            const endTime = lastAnswerTime + 3600000; // 1시간
            
            timerInterval = setInterval(() => {
                const now = Date.now();
                const remaining = Math.max(0, endTime - now);
                
                if (remaining === 0) {
                    clearInterval(timerInterval);
                    timerContainer.style.display = 'none';
                    // 시간이 다 되면 자동으로 다음 노드 열기
                    unlockConnectedNodes(currentNode);
                    return;
                }
                
                const hours = Math.floor(remaining / 3600000);
                const minutes = Math.floor((remaining % 3600000) / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                
                document.getElementById('timerDisplay').textContent = 
                    `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }, 1000);
        }

        // 아바타 메시지 업데이트
        function updateAvatarMessage(type) {
            const speech = document.getElementById('avatarSpeech');
            speech.style.animation = 'none';
            
            setTimeout(() => {
                speech.textContent = avatarMessages[type] || avatarMessages.welcome;
                speech.style.animation = 'slideIn 0.5s ease-out';
            }, 100);
        }

        // 아바타 흔들기
        function shakeAvatar() {
            const avatar = document.getElementById('avatar');
            avatar.style.animation = 'none';
            
            setTimeout(() => {
                avatar.style.animation = 'shake 0.5s ease-in-out, float 3s ease-in-out infinite';
            }, 100);
        }

        // 진행 상황 저장
        function saveAnswer(nodeId, answer) {
            const progress = JSON.parse(localStorage.getItem('mathJourney') || '{}');
            
            if (!progress.answers) progress.answers = {};
            progress.answers[nodeId] = {
                text: answer,
                timestamp: Date.now()
            };
            
            progress.completed = Array.from(completedNodes);
            progress.unlocked = Array.from(unlockedNodes);
            progress.lastAnswerTime = lastAnswerTime;
            
            localStorage.setItem('mathJourney', JSON.stringify(progress));
        }

        // 진행 상황 불러오기
        function loadProgress() {
            const progress = JSON.parse(localStorage.getItem('mathJourney') || '{}');
            
            if (progress.completed) {
                progress.completed.forEach(id => completedNodes.add(id));
            }
            
            if (progress.unlocked) {
                progress.unlocked.forEach(id => unlockedNodes.add(id));
            }
            
            if (progress.lastAnswerTime) {
                lastAnswerTime = progress.lastAnswerTime;
                const remaining = 3600000 - (Date.now() - lastAnswerTime);
                if (remaining > 0) {
                    startTimer();
                }
            }
            
            // UI 업데이트
            createConstellation();
        }

        // 완료된 질문 보기
        function showCompletedQuestion(nodeId) {
            const progress = JSON.parse(localStorage.getItem('mathJourney') || '{}');
            const answer = progress.answers?.[nodeId];
            
            if (!answer) return;
            
            const panel = document.getElementById('contentPanel');
            const question = questions[nodeId];
            
            document.getElementById('questionTitle').textContent = question.title + ' (완료됨)';
            
            const content = `
                <div class="question-card">
                    <p class="question-text">${question.text}</p>
                    <div style="background: #0f1228; border: 1px solid #3a3d5a; border-radius: 10px; padding: 15px; margin-top: 15px;">
                        <p style="color: #888; font-size: 0.9em; margin-bottom: 10px;">작성한 답변:</p>
                        <p style="line-height: 1.6;">${answer.text}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('panelContent').innerHTML = content;
            panel.classList.add('open');
        }

        // 아바타 클릭 이벤트
        document.getElementById('avatar').addEventListener('click', () => {
            const messages = Object.values(avatarMessages.encouragement);
            const randomMessage = messages[Math.floor(Math.random() * messages.length)];
            updateAvatarMessage('encouragement');
        });

        // shake 애니메이션 정의
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);

        // 초기화 실행
        init();
    </script>
</body>
</html>