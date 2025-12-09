<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Mode - Welcome Session</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;700;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Particle Background */
        .particles {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }

        /* Main Container */
        .container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Scene Container */
        .scene {
            position: relative;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            padding: 60px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Scene Transitions */
        .scene.fade-out {
            animation: fadeOut 0.5s forwards;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateY(-30px);
            }
        }

        /* Typography */
        .typing-text {
            font-size: 1.2em;
            line-height: 1.8;
            color: #2d3748;
            min-height: 60px;
        }

        .typing-cursor {
            display: inline-block;
            width: 3px;
            height: 1.2em;
            background: #667eea;
            animation: blink 1s infinite;
            vertical-align: text-bottom;
            margin-left: 2px;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }

        /* Mission Cards */
        .mission-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 25px;
            margin: 20px 0;
            color: white;
            transform: translateX(-50px);
            opacity: 0;
            animation: slideIn 0.6s forwards;
            position: relative;
            overflow: hidden;
        }

        .mission-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slideIn {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .mission-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 15px;
        }

        /* Progress Bar */
        .progress-container {
            margin: 30px 0;
            background: #e2e8f0;
            border-radius: 50px;
            height: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 50px;
            width: 0;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shine 2s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Streak Counter */
        .streak-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
        }

        .streak-day {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s;
            position: relative;
        }

        .streak-day.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .streak-day.active::after {
            content: '🔥';
            position: absolute;
            top: -15px;
            font-size: 20px;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        /* Buttons */
        .btn-continue {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            margin-top: 30px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-continue::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-continue:hover::before {
            left: 100%;
        }

        /* Achievement Notification */
        .achievement {
            position: fixed;
            top: 30px;
            right: -400px;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            transition: right 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .achievement.show {
            right: 30px;
        }

        .achievement-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        /* Formula Animation */
        .formula {
            font-size: 2em;
            color: #667eea;
            text-align: center;
            margin: 30px 0;
            opacity: 0;
            animation: formulaAppear 1s forwards;
        }

        @keyframes formulaAppear {
            to {
                opacity: 1;
                transform: scale(1.1);
            }
        }

        /* Scene specific styles */
        .title {
            font-size: 2.5em;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin-bottom: 30px;
            opacity: 0;
            animation: titlePulse 1s forwards 0.5s;
        }

        @keyframes titlePulse {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .emoji-rain {
            position: fixed;
            top: -50px;
            font-size: 30px;
            animation: rain 3s linear;
            z-index: 100;
        }

        @keyframes rain {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        /* Timer Display */
        .timer-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin: 30px 0;
        }

        .timer-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2em;
            font-weight: bold;
            position: relative;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .timer-label {
            font-size: 0.4em;
            opacity: 0.9;
        }

        /* Reward Box */
        .reward-box {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            color: white;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }

        .reward-box::before {
            content: '🎁';
            position: absolute;
            font-size: 100px;
            opacity: 0.1;
            right: -20px;
            top: -20px;
            transform: rotate(-15deg);
        }

        .hidden {
            display: none;
        }

        /* Loading dots */
        .loading-dots {
            display: inline-block;
        }

        .loading-dots span {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #667eea;
            margin: 0 3px;
            animation: loadingDot 1.4s infinite;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes loadingDot {
            0%, 60%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            30% {
                transform: scale(1.5);
                opacity: 0.5;
            }
        }
    </style>
</head>
<body>
    <!-- Particle Background -->
    <div class="particles" id="particles"></div>

    <!-- Achievement Notification -->
    <div class="achievement" id="achievement">
        <div class="achievement-icon">⚡</div>
        <div>
            <div style="font-weight: bold;">Mission Mode 활성화!</div>
            <div style="color: #718096; font-size: 0.9em;">당신만의 성장 시스템이 시작됩니다</div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Scene 1: Introduction -->
        <div class="scene" id="scene1">
            <h1 class="title">⚡ Mission Mode</h1>
            <div class="typing-text" id="typing1"></div>
            <div class="progress-container">
                <div class="progress-bar" id="progress1"></div>
            </div>
            <button class="btn-continue hidden" id="btn1" onclick="nextScene(2)">시작하기 →</button>
        </div>

        <!-- Scene 2: Mission Philosophy -->
        <div class="scene hidden" id="scene2">
            <div class="formula">작은 성취 × 매일 = 큰 도약</div>
            <div class="typing-text" id="typing2"></div>
            <div class="mission-card" style="animation-delay: 0.3s;">
                <span class="mission-number">1</span>
                하루 5개의 미션으로 시작합니다
            </div>
            <div class="mission-card" style="animation-delay: 0.6s;">
                <span class="mission-number">2</span>
                10분 집중, 5분 휴식의 포모도로
            </div>
            <div class="mission-card" style="animation-delay: 0.9s;">
                <span class="mission-number">3</span>
                7일 스트릭 유지가 첫 목표입니다
            </div>
            <button class="btn-continue hidden" id="btn2" onclick="nextScene(3)">다음 단계 →</button>
        </div>

        <!-- Scene 3: Streak System -->
        <div class="scene hidden" id="scene3">
            <h2 style="text-align: center; color: #2d3748; margin-bottom: 30px;">🔥 스트릭 시스템</h2>
            <div class="typing-text" id="typing3"></div>
            <div class="streak-container" id="streakContainer">
                <div class="streak-day">1</div>
                <div class="streak-day">2</div>
                <div class="streak-day">3</div>
                <div class="streak-day">4</div>
                <div class="streak-day">5</div>
                <div class="streak-day">6</div>
                <div class="streak-day">7</div>
            </div>
            <div class="progress-container">
                <div class="progress-bar" id="progress3"></div>
            </div>
            <button class="btn-continue hidden" id="btn3" onclick="nextScene(4)">보상 시스템 보기 →</button>
        </div>

        <!-- Scene 4: Reward System -->
        <div class="scene hidden" id="scene4">
            <h2 style="text-align: center; color: #2d3748; margin-bottom: 30px;">🎁 주간 보상 시스템</h2>
            <div class="typing-text" id="typing4"></div>
            <div class="reward-box">
                <h3>이번 주 달성률 85% 이상 시</h3>
                <p style="font-size: 1.2em; margin-top: 15px;">특별 보상이 기다립니다!</p>
            </div>
            <div class="timer-display">
                <div class="timer-circle">
                    <span>10분</span>
                    <span class="timer-label">집중</span>
                </div>
                <span style="font-size: 2em;">→</span>
                <div class="timer-circle" style="background: linear-gradient(135deg, #56ccf2, #2f80ed);">
                    <span>5분</span>
                    <span class="timer-label">휴식</span>
                </div>
            </div>
            <button class="btn-continue hidden" id="btn4" onclick="nextScene(5)">맞춤 설정하기 →</button>
        </div>

        <!-- Scene 5: Personalization -->
        <div class="scene hidden" id="scene5">
            <h2 style="text-align: center; color: #2d3748; margin-bottom: 30px;">🎯 당신만의 Mission Mode</h2>
            <div class="typing-text" id="typing5"></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0;">
                <div class="mission-card" style="animation-delay: 0.2s;">
                    <div>📊 현재 수준 파악</div>
                    <small style="opacity: 0.9;">약점 분석 & 맞춤 미션</small>
                </div>
                <div class="mission-card" style="animation-delay: 0.4s;">
                    <div>🎮 게임화 시스템</div>
                    <small style="opacity: 0.9;">성취 포인트 & 레벨업</small>
                </div>
                <div class="mission-card" style="animation-delay: 0.6s;">
                    <div>📈 적응형 난이도</div>
                    <small style="opacity: 0.9;">성공률 기반 자동 조정</small>
                </div>
                <div class="mission-card" style="animation-delay: 0.8s;">
                    <div>🤝 실시간 피드백</div>
                    <small style="opacity: 0.9;">즉각적인 성과 확인</small>
                </div>
            </div>
            <button class="btn-continue hidden" id="btn5" onclick="startMissionMode()">
                Mission Mode 시작하기 ⚡
            </button>
        </div>

        <!-- Scene 6: Ready to Start -->
        <div class="scene hidden" id="scene6">
            <h1 class="title">준비 완료!</h1>
            <div style="text-align: center; margin: 40px 0;">
                <div style="font-size: 5em; animation: bounce 2s infinite;">⚡</div>
                <h2 style="color: #2d3748; margin: 20px 0;">당신의 첫 미션이 준비되었습니다</h2>
                <p style="color: #718096; font-size: 1.1em;">작은 성취가 모여 큰 변화를 만듭니다</p>
            </div>
            <div class="loading-dots" style="text-align: center; margin: 30px 0;">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="formula" style="margin-top: 40px;">
                "오늘의 미션 5개가 내일의 실력이 됩니다"
            </div>
        </div>
    </div>

    <script>
        // Initialize particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Typing effect
        function typeText(elementId, text, callback) {
            const element = document.getElementById(elementId);
            element.innerHTML = '';
            let index = 0;
            
            function type() {
                if (index < text.length) {
                    element.innerHTML = text.substring(0, index + 1) + '<span class="typing-cursor"></span>';
                    index++;
                    setTimeout(type, 50);
                } else {
                    element.innerHTML = text;
                    if (callback) callback();
                }
            }
            type();
        }

        // Scene transitions
        let currentScene = 1;
        
        function nextScene(sceneNumber) {
            const currentSceneEl = document.getElementById(`scene${currentScene}`);
            const nextSceneEl = document.getElementById(`scene${sceneNumber}`);
            
            currentSceneEl.classList.add('fade-out');
            
            setTimeout(() => {
                currentSceneEl.classList.add('hidden');
                nextSceneEl.classList.remove('hidden');
                
                // Initialize scene-specific animations
                initScene(sceneNumber);
                currentScene = sceneNumber;
            }, 500);
        }

        // Initialize scenes
        function initScene(sceneNumber) {
            switch(sceneNumber) {
                case 1:
                    typeText('typing1', 
                        '안녕하세요! 수학 실력을 폭발적으로 성장시킬 준비가 되셨나요?\n' +
                        '당신을 위한 특별한 학습 시스템, Mission Mode를 소개합니다.\n' +
                        '작은 성취의 연쇄 반응으로 큰 도약을 만들어갑니다.',
                        () => {
                            document.getElementById('progress1').style.width = '100%';
                            setTimeout(() => {
                                document.getElementById('btn1').classList.remove('hidden');
                            }, 500);
                        }
                    );
                    break;
                    
                case 2:
                    typeText('typing2',
                        '매일 5개의 명확한 미션.\n' +
                        '각 미션은 당신의 현재 수준에 완벽하게 맞춰집니다.\n' +
                        '실패해도 괜찮아요. 다음날 7개로 보충할 기회가 있습니다.',
                        () => {
                            setTimeout(() => {
                                document.getElementById('btn2').classList.remove('hidden');
                                showAchievement();
                            }, 1500);
                        }
                    );
                    break;
                    
                case 3:
                    typeText('typing3',
                        '7일 연속 미션 완수가 첫 목표입니다.\n' +
                        '매일의 작은 성공이 습관이 되는 순간을 경험하세요.',
                        () => {
                            animateStreak();
                            document.getElementById('progress3').style.width = '70%';
                            setTimeout(() => {
                                document.getElementById('btn3').classList.remove('hidden');
                            }, 3000);
                        }
                    );
                    break;
                    
                case 4:
                    typeText('typing4',
                        '주간 달성률 80% 이상 시 특별 보상!\n' +
                        '당신의 노력은 반드시 보상받습니다.\n' +
                        '포모도로 타이머로 집중력도 함께 키워보세요.',
                        () => {
                            setTimeout(() => {
                                document.getElementById('btn4').classList.remove('hidden');
                                createEmojiRain();
                            }, 1000);
                        }
                    );
                    break;
                    
                case 5:
                    typeText('typing5',
                        'AI가 당신의 학습 패턴을 분석합니다.\n' +
                        '매주 더 나은 미션으로 진화합니다.\n' +
                        '당신만의 완벽한 학습 리듬을 찾아드립니다.',
                        () => {
                            setTimeout(() => {
                                document.getElementById('btn5').classList.remove('hidden');
                            }, 2000);
                        }
                    );
                    break;
                    
                case 6:
                    setTimeout(() => {
                        createEmojiRain();
                        setTimeout(() => {
                            createEmojiRain();
                        }, 1000);
                    }, 500);
                    break;
            }
        }

        // Animate streak days
        function animateStreak() {
            const days = document.querySelectorAll('.streak-day');
            days.forEach((day, index) => {
                setTimeout(() => {
                    day.classList.add('active');
                }, index * 300);
            });
        }

        // Show achievement notification
        function showAchievement() {
            const achievement = document.getElementById('achievement');
            achievement.classList.add('show');
            setTimeout(() => {
                achievement.classList.remove('show');
            }, 3000);
        }

        // Create emoji rain effect
        function createEmojiRain() {
            const emojis = ['⚡', '🎯', '🔥', '✨', '💪', '🚀'];
            for (let i = 0; i < 10; i++) {
                setTimeout(() => {
                    const emoji = document.createElement('div');
                    emoji.className = 'emoji-rain';
                    emoji.style.left = Math.random() * 100 + '%';
                    emoji.textContent = emojis[Math.floor(Math.random() * emojis.length)];
                    document.body.appendChild(emoji);
                    
                    setTimeout(() => {
                        emoji.remove();
                    }, 3000);
                }, i * 200);
            }
        }

        // Start Mission Mode
        function startMissionMode() {
            nextScene(6);
            setTimeout(() => {
                // Here you would typically transition to the actual mission interface
                console.log('Mission Mode Started!');
            }, 3000);
        }

        // Initialize
        window.onload = function() {
            createParticles();
            initScene(1);
        };
    </script>
</body>
</html>