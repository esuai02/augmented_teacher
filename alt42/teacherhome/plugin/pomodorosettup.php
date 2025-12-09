<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학공부 30분의 기적 - 지치기 전에 멈추고 정리하고 충전한다!</title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@300;400;600;700&family=Gmarket+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Pretendard', 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .story-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease forwards;
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-title {
            font-size: 2.5em;
            color: #5f27cd;
            text-align: center;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .story-text {
            font-size: 1.2em;
            line-height: 1.8;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        
        .highlight {
            background: linear-gradient(120deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .timer-demo {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        
        .timer-display {
            font-size: 4em;
            font-weight: bold;
            color: #e74c3c;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            animation: timerPulse 1s ease-in-out infinite;
        }
        
        @keyframes timerPulse {
            0%, 100% { transform: scale(1); color: #e74c3c; }
            50% { transform: scale(1.02); color: #ff6b6b; }
        }
        
        .timer-running {
            animation: speedTimer 0.5s ease-in-out infinite;
        }
        
        @keyframes speedTimer {
            0%, 100% { transform: scale(1) rotate(0deg); }
            25% { transform: scale(1.03) rotate(0.5deg); }
            75% { transform: scale(1.03) rotate(-0.5deg); }
        }
        
        .timer-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.2em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .timer-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
        }
        
        .diary-preview {
            background: #fffbf0;
            border: 3px dashed #f39c12;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            position: relative;
        }
        
        .diary-entry {
            font-family: 'Gmarket Sans', 'Cafe24 Ssurround', cursive;
            font-size: 1.6em;
            color: #2c3e50;
            line-height: 1.6;
            font-weight: 300;
        }
        
        .effect-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .effect-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .effect-icon {
            font-size: 3em;
            margin-bottom: 10px;
        }
        
        .send-section {
            text-align: center;
            margin-top: 50px;
            padding: 40px;
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            border-radius: 20px;
            color: white;
        }
        
        .send-button {
            background: white;
            color: #ff6b6b;
            border: none;
            padding: 20px 60px;
            border-radius: 50px;
            font-size: 1.5em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .floating-emoji {
            position: absolute;
            font-size: 2em;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #ecf0f1;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
            width: 0%;
            transition: width 1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .satisfaction-tracker {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 30px;
            margin: 20px 0;
        }
        
        .satisfaction-item {
            margin-bottom: 30px;
        }
        
        .satisfaction-item h3 {
            font-size: 1.5em;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .satisfaction-bar {
            width: 100%;
            height: 40px;
            background: #ecf0f1;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .satisfaction-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b6b 0%, #feca57 50%, #48dbfb 100%);
            width: 0%;
            transition: width 0.8s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .satisfaction-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .satisfaction-buttons button {
            font-size: 2em;
            background: white;
            border: 3px solid #ddd;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .satisfaction-buttons button:hover {
            transform: scale(1.2);
            border-color: #ff6b6b;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .satisfaction-buttons button.selected {
            border-color: #ff6b6b;
            background: #fff5f5;
            transform: scale(1.1);
        }
        
        .overall-score {
            text-align: center;
            margin-top: 40px;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
        }
        
        .overall-score h2 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .score-message {
            font-size: 1.3em;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="story-section" style="animation-delay: 0.2s;">
            <h1 class="hero-title">📐 수학공부 30분의 기적 📐</h1>
            
            <div class="story-text">
                <p><span class="highlight">수학 지치기 전에 멈추고</span>, <span class="highlight">정리하고</span>, <span class="highlight">충전한다!</span></p>
                <p>하루 종일 수학할 수 있는 <span class="highlight">기적의 수학공부법</span>을 알려줄게!</p>
                <p>30분만 집중하면 수학 실력이 달라져! 🎯</p>
            </div>
            
            <div class="floating-emoji" style="top: 20px; right: 50px;">📚</div>
            <div class="floating-emoji" style="bottom: 30px; left: 40px; animation-delay: 1s;">✨</div>
        </div>

        <div class="story-section" style="animation-delay: 0.4s;">
            <h2 style="color: #e74c3c; margin-bottom: 20px;">🍅 수학 30분 집중 타이머 체험해보기</h2>
            
            <div class="story-text">
                <p>자, 이제 실제로 <span class="highlight">수학 30분 집중 타이머</span>를 체험해볼까?</p>
                <p>아래 버튼을 눌러서 수학 집중 타이머가 어떻게 작동하는지 확인해봐!</p>
                <p><span class="highlight">🚀 비밀: 수학 시간이 초고속으로 가는 마법 타이머!</span> 30분이 눈 깜짝할 사이에 지나갈 거야! 💨</p>
            </div>
            
            <div class="timer-demo">
                <div class="timer-display" id="timer">30:00</div>
                <button class="timer-button" onclick="startTimer()">🚀 초고속 마법 타이머 시작 💨</button>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress">0%</div>
                </div>
            </div>
        </div>

        <div class="story-section" style="animation-delay: 0.5s;">
            <h2 style="color: #ff6b6b; margin-bottom: 20px;">📊 나의 집중도 & 만족도 체크</h2>
            
            <div class="story-text">
                <p>30분 공부 후 <span class="highlight">나의 상태</span>를 체크해보자!</p>
                <p>매번 기록하면 나만의 <span class="highlight">성장 그래프</span>를 볼 수 있어!</p>
            </div>
            
            <div class="satisfaction-tracker">
                <div class="satisfaction-item">
                    <h3>🧠 집중도</h3>
                    <div class="satisfaction-bar">
                        <div class="satisfaction-fill" id="focus-bar" data-value="0">0%</div>
                    </div>
                    <div class="satisfaction-buttons">
                        <button onclick="setSatisfaction('focus', 20)">😴</button>
                        <button onclick="setSatisfaction('focus', 40)">😐</button>
                        <button onclick="setSatisfaction('focus', 60)">🙂</button>
                        <button onclick="setSatisfaction('focus', 80)">😊</button>
                        <button onclick="setSatisfaction('focus', 100)">🤩</button>
                    </div>
                </div>
                
                <div class="satisfaction-item">
                    <h3>💖 만족도</h3>
                    <div class="satisfaction-bar">
                        <div class="satisfaction-fill" id="satisfaction-bar" data-value="0">0%</div>
                    </div>
                    <div class="satisfaction-buttons">
                        <button onclick="setSatisfaction('satisfaction', 20)">😭</button>
                        <button onclick="setSatisfaction('satisfaction', 40)">😔</button>
                        <button onclick="setSatisfaction('satisfaction', 60)">😊</button>
                        <button onclick="setSatisfaction('satisfaction', 80)">😍</button>
                        <button onclick="setSatisfaction('satisfaction', 100)">🚀</button>
                    </div>
                </div>
                
                <div class="satisfaction-item">
                    <h3>⚡ 에너지</h3>
                    <div class="satisfaction-bar">
                        <div class="satisfaction-fill" id="energy-bar" data-value="0">0%</div>
                    </div>
                    <div class="satisfaction-buttons">
                        <button onclick="setSatisfaction('energy', 20)">🔋</button>
                        <button onclick="setSatisfaction('energy', 40)">🔋🔋</button>
                        <button onclick="setSatisfaction('energy', 60)">🔋🔋🔋</button>
                        <button onclick="setSatisfaction('energy', 80)">⚡⚡</button>
                        <button onclick="setSatisfaction('energy', 100)">🚀⚡</button>
                    </div>
                </div>
                
                <div class="overall-score">
                    <h2>종합 점수: <span id="total-score">0</span>/100</h2>
                    <div class="score-message" id="score-message">아직 측정하지 않았어요!</div>
                </div>
            </div>
        </div>

        <div class="story-section" style="animation-delay: 0.6s;">
            <h2 style="color: #f39c12; margin-bottom: 20px;">📝 3줄 수학일기 미리보기</h2>
            
            <div class="story-text">
                <p>30분이 끝나면, 이렇게 <span class="highlight">3줄 수학일기</span>를 써보는 거야!</p>
                <p>수학 개념이 머릿속에 정리되면서 <span class="highlight">하루 종일 수학공부</span>의 비밀이 여기 있어!</p>
            </div>
            
            <div class="diary-preview">
                <div class="diary-entry" id="diaryContent">
                    <p>📅 2024년 3월 15일 - 2교시 수학</p>
                    <p>1️⃣ 오늘 이차함수 그래프를 배웠다. y=ax²+bx+c에서 a값이 그래프 모양을 결정한다는 게 완전 신기했다!</p>
                    <p>2️⃣ 처음엔 복잡해 보였는데, 직접 그려보니까 이해가 됐다. a가 음수면 아래로 볼록, 양수면 위로 볼록!</p>
                    <p>3️⃣ 다음엔 꼭짓점 구하는 공식을 완벽하게 외워야지! 30분 더 하면 완전 마스터할 것 같아! 💪</p>
                </div>
            </div>
            
            <button class="timer-button" style="background: #f39c12;" onclick="writeDiary()">나도 일기 써보기 ✏️</button>
        </div>

        <div class="story-section" style="animation-delay: 0.8s;">
            <h2 style="color: #9b59b6; margin-bottom: 20px;">🌟 수학 마법 타이머의 놀라운 효과!</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div class="effect-card">
                    <div class="effect-icon">🧠</div>
                    <h3>수학 개념 완전 정착</h3>
                    <p>짧은 시간에 집중하니까 공식이 머릿속에 쏙쏙! 시험 때 자동으로 나와요</p>
                </div>
                
                <div class="effect-card">
                    <div class="effect-icon">⚡</div>
                    <h3>수학 포기 방지</h3>
                    <p>시간이 초고속으로 가니까 수학이 지루할 틈이 전혀 없어! 눈 깜짝할 사이에 끝나요</p>
                </div>
                
                <div class="effect-card">
                    <div class="effect-icon">📈</div>
                    <h3>수학 점수 급상승</h3>
                    <p>짧고 강한 집중의 힘! 2주만 해봐, 수학 점수가 눈에 띄게 올라갈 거예요</p>
                </div>
                
                <div class="effect-card">
                    <div class="effect-icon">😊</div>
                    <h3>수학이 게임이 돼요</h3>
                    <p>타이머가 빨리 가니까 수학하는 기분이 게임 같아! "벌써 끝났네?" 소리가 절로 나와요!</p>
                </div>
            </div>
        </div>

        <div class="story-section" style="animation-delay: 1s;">
            <h2 style="color: #e74c3c; margin-bottom: 20px;">💎 특별한 선물!</h2>
            
            <div class="story-text">
                <p>지금 시작하면 <span class="highlight">1주일 챌린지 달력</span>과</p>
                <p><span class="highlight">수학일기 템플릿 10개</span>를 무료로 드려요!</p>
                <p>친구들과 함께 도전하면 더 재밌어요! 🎉</p>
            </div>
        </div>

        <div class="send-section">
            <h2 style="font-size: 2em; margin-bottom: 20px;">🚀 이 인터페이스를 학생에게 전달하여 추적하시겠습니까?</h2>
            <p style="font-size: 1.3em; margin-bottom: 30px;">학생의 학습 진도와 성과를 실시간으로 확인할 수 있습니다!</p>
            <button class="send-button" onclick="sendInterface()">발송하기 📮</button>
        </div>
    </div>

    <script>
        let timerInterval;
        let timeLeft = 1800; // 30분 = 1800초
        let isRunning = false;

        function startTimer() {
            if (isRunning) return;
            
            isRunning = true;
            const button = document.querySelector('.timer-button');
            const timerDisplay = document.getElementById('timer');
            
            button.textContent = '🚀 초초고속 집중 중... 💨';
            button.style.background = '#e74c3c';
            timerDisplay.classList.add('timer-running');
            
            timerInterval = setInterval(() => {
                timeLeft -= 10; // 1초마다 10초씩 초고속 감소!
                updateDisplay();
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerDisplay.classList.remove('timer-running');
                    alert('🎉 와! 벌써 30분이 지났네! 시간 가는 줄도 모르고 집중했구나! 이제 만족도를 체크하고 일기를 써볼까?');
                    resetTimer();
                }
            }, 1000);
        }

        function updateDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            const progress = ((1800 - timeLeft) / 1800) * 100;
            const progressBar = document.getElementById('progress');
            progressBar.style.width = progress + '%';
            progressBar.textContent = Math.floor(progress) + '%';
        }

        function resetTimer() {
            timeLeft = 1800;
            isRunning = false;
            updateDisplay();
            const button = document.querySelector('.timer-button');
            const timerDisplay = document.getElementById('timer');
            
            button.textContent = '🚀 초고속 마법 타이머 시작 💨';
            button.style.background = '#3498db';
            timerDisplay.classList.remove('timer-running');
            document.getElementById('progress').style.width = '0%';
        }

        function writeDiary() {
            const diaryContent = document.getElementById('diaryContent');
            diaryContent.innerHTML = `
                <p>📅 ${new Date().toLocaleDateString('ko-KR')}</p>
                <p contenteditable="true" style="border: 2px dashed #f39c12; padding: 10px; margin: 10px 0;">
                    1️⃣ 오늘 배운 내용을 써보세요...
                </p>
                <p contenteditable="true" style="border: 2px dashed #f39c12; padding: 10px; margin: 10px 0;">
                    2️⃣ 어려웠던 점이나 깨달은 점은?
                </p>
                <p contenteditable="true" style="border: 2px dashed #f39c12; padding: 10px; margin: 10px 0;">
                    3️⃣ 내일 공부할 내용은?
                </p>
            `;
            
            diaryContent.scrollIntoView({ behavior: 'smooth' });
        }

        function setSatisfaction(type, value) {
            const barId = type + '-bar';
            const bar = document.getElementById(barId);
            
            // 이전 선택 해제
            const buttons = bar.parentElement.nextElementSibling.querySelectorAll('button');
            buttons.forEach(btn => btn.classList.remove('selected'));
            
            // 현재 버튼 선택
            event.target.classList.add('selected');
            
            // 바 업데이트
            bar.style.width = value + '%';
            bar.textContent = value + '%';
            bar.setAttribute('data-value', value);
            
            // 총점 계산
            updateTotalScore();
        }
        
        function updateTotalScore() {
            const focusValue = parseInt(document.getElementById('focus-bar').getAttribute('data-value')) || 0;
            const satisfactionValue = parseInt(document.getElementById('satisfaction-bar').getAttribute('data-value')) || 0;
            const energyValue = parseInt(document.getElementById('energy-bar').getAttribute('data-value')) || 0;
            
            const totalScore = Math.round((focusValue + satisfactionValue + energyValue) / 3);
            document.getElementById('total-score').textContent = totalScore;
            
            const messageElement = document.getElementById('score-message');
            let message = '';
            
            if (totalScore === 0) {
                message = '아직 측정하지 않았어요!';
            } else if (totalScore < 30) {
                message = '오늘은 좀 힘들었네요. 내일은 더 나아질 거예요! 💪';
            } else if (totalScore < 50) {
                message = '나쁘지 않아요! 조금씩 발전하고 있어요 🌱';
            } else if (totalScore < 70) {
                message = '좋은 상태예요! 이 페이스를 유지해봐요 😊';
            } else if (totalScore < 90) {
                message = '정말 잘하고 있어요! 완전 집중 모드네요 🔥';
            } else {
                message = '와! 완벽한 30분이었네요! 천재인가요? 🚀✨';
            }
            
            messageElement.textContent = message;
        }

        function sendInterface() {
            const button = document.querySelector('.send-button');
            button.textContent = '발송 중... ✈️';
            button.style.background = '#95a5a6';
            
            setTimeout(() => {
                alert('✅ 성공적으로 발송되었습니다! 학생의 학습 진도를 추적할 수 있습니다.');
                button.textContent = '발송 완료! ✅';
                button.style.background = '#27ae60';
            }, 2000);
        }

        // 페이지 로드 시 애니메이션
        window.addEventListener('load', () => {
            const sections = document.querySelectorAll('.story-section');
            sections.forEach((section, index) => {
                section.style.animationDelay = `${index * 0.2}s`;
            });
        });
    </script>
</body>
</html>