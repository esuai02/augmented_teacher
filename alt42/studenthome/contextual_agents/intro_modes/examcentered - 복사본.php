<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✏️ 성과 집중형 - Welcome Session</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

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
            background: white;
            border-radius: 50%;
            opacity: 0;
            animation: floatUp 15s infinite;
        }

        @keyframes floatUp {
            0% {
                opacity: 0;
                transform: translateY(100vh) scale(0);
            }
            10% {
                opacity: 0.4;
            }
            90% {
                opacity: 0.4;
            }
            100% {
                opacity: 0;
                transform: translateY(-100vh) scale(1.5);
            }
        }

        .container {
            position: relative;
            z-index: 10;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .story-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            padding: 60px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            opacity: 0;
            transform: translateY(30px);
            animation: slideUp 0.8s forwards;
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .scene {
            display: none;
            animation: fadeIn 0.6s;
        }

        .scene.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .title-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        h1 {
            font-size: 36px;
            color: #1a1a2e;
            margin-bottom: 30px;
            line-height: 1.3;
        }

        .typing-text {
            font-size: 18px;
            line-height: 1.8;
            color: #444;
            margin-bottom: 20px;
            min-height: 100px;
        }

        .highlight {
            background: linear-gradient(to right, #ffeaa7, #fdcb6e);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            animation: highlightPop 0.5s;
        }

        @keyframes highlightPop {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin: 40px 0 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
            width: 0;
            transition: width 0.8s ease;
        }

        .cta-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            opacity: 0;
            transform: translateY(20px);
            margin-top: 30px;
        }

        .cta-button.show {
            opacity: 1;
            transform: translateY(0);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px 0;
            opacity: 0;
            animation: fadeInUp 0.8s 0.5s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            border-radius: 15px;
            transform: translateY(20px);
            opacity: 0;
            animation: cardReveal 0.6s forwards;
        }

        .feature-card:nth-child(1) { animation-delay: 0.2s; }
        .feature-card:nth-child(2) { animation-delay: 0.4s; }
        .feature-card:nth-child(3) { animation-delay: 0.6s; }
        .feature-card:nth-child(4) { animation-delay: 0.8s; }

        @keyframes cardReveal {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .feature-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .feature-desc {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }

        .timeline {
            position: relative;
            padding: 30px 0;
            opacity: 0;
        }

        .timeline.show {
            opacity: 1;
            animation: timelineReveal 1s;
        }

        @keyframes timelineReveal {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            opacity: 0;
            transform: translateX(-30px);
        }

        .timeline-item.show {
            animation: itemSlide 0.6s forwards;
        }

        @keyframes itemSlide {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .timeline-marker {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .timeline-desc {
            color: #666;
            font-size: 14px;
        }

        .score-display {
            font-size: 48px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin: 30px 0;
            opacity: 0;
        }

        .score-display.show {
            animation: scoreReveal 1s forwards;
        }

        @keyframes scoreReveal {
            0% {
                opacity: 0;
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .magic-text {
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradient 3s linear infinite;
            font-weight: 600;
        }

        @keyframes gradient {
            to {
                background-position: 200% center;
            }
        }

        .floating-emoji {
            position: absolute;
            font-size: 30px;
            animation: float 3s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .skip-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: #666;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .skip-button:hover {
            background: rgba(255,255,255,0.4);
        }

        @media (max-width: 768px) {
            .story-card {
                padding: 40px 30px;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .feature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="container">
        <div class="story-card">
            <button class="skip-button" onclick="skipToEnd()">건너뛰기 →</button>
            
            <!-- Scene 1: Opening -->
            <div class="scene active" id="scene1">
                <span class="title-badge">✏️ EXAM MODE</span>
                <h1>시험은 <span class="magic-text">프로젝트</span>,<br>점수는 <span class="magic-text">결과물</span>이다</h1>
                <div class="typing-text" id="text1"></div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 14%"></div>
                </div>
                <button class="cta-button" onclick="nextScene(2)">맞아, 이게 내가 원하던 거야</button>
                <div class="floating-emoji" style="top: 100px; right: 50px;">📊</div>
                <div class="floating-emoji" style="bottom: 80px; left: 40px; animation-delay: 1s;">🎯</div>
            </div>

            <!-- Scene 2: D-30 System -->
            <div class="scene" id="scene2">
                <span class="title-badge">D-30 SYSTEM</span>
                <h1>당신의 시험은<br><span class="magic-text">D-30</span>부터 시작됩니다</h1>
                <div class="typing-text" id="text2"></div>
                <div class="timeline" id="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker">D-30</div>
                        <div class="timeline-content">
                            <div class="timeline-title">기출분석 & 전략수립</div>
                            <div class="timeline-desc">당신의 약점을 정확히 파악합니다</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker">D-14</div>
                        <div class="timeline-content">
                            <div class="timeline-title">실전모의 집중훈련</div>
                            <div class="timeline-desc">주 2회 실전과 같은 환경에서</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker">D-7</div>
                        <div class="timeline-content">
                            <div class="timeline-title">파이널 리뷰 & 컨디션</div>
                            <div class="timeline-desc">40문항 핵심정리와 최상의 컨디션</div>
                        </div>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 28%"></div>
                </div>
                <button class="cta-button" onclick="nextScene(3)">체계적이네! 계속 보여줘</button>
            </div>

            <!-- Scene 3: Triple System -->
            <div class="scene" id="scene3">
                <span class="title-badge">TRIPLE SYSTEM</span>
                <h1>성공을 보장하는<br><span class="magic-text">3가지 핵심</span></h1>
                <div class="typing-text" id="text3"></div>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">📚</div>
                        <div class="feature-title">기출 3회독 시스템</div>
                        <div class="feature-desc">반복이 만드는 완벽한 패턴 인식</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📝</div>
                        <div class="feature-title">백지복습 Daily</div>
                        <div class="feature-desc">매일 5분, 기억을 각인시키는 마법</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⏱️</div>
                        <div class="feature-title">실전모의 주 2회</div>
                        <div class="feature-desc">시험장 긴장감을 일상으로</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🎯</div>
                        <div class="feature-title">약점 집중 보정</div>
                        <div class="feature-desc">20점 상승을 위한 맞춤 처방</div>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 42%"></div>
                </div>
                <button class="cta-button" onclick="nextScene(4)">완벽해! 더 알고 싶어</button>
            </div>

            <!-- Scene 4: Intelligence System -->
            <div class="scene" id="scene4">
                <span class="title-badge">8-INTELLIGENCE</span>
                <h1>당신을 위한<br><span class="magic-text">8가지 지능</span>이 작동합니다</h1>
                <div class="typing-text" id="text4"></div>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🌍</div>
                        <div class="feature-title">W: 세계관 정렬</div>
                        <div class="feature-desc">시험=프로젝트 마인드셋</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🧠</div>
                        <div class="feature-title">X: 문맥 지능</div>
                        <div class="feature-desc">출제경향 완벽 분석</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <div class="feature-title">S: 구조 지능</div>
                        <div class="feature-desc">KPI 기반 성과 관리</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <div class="feature-title">P: 절차 지능</div>
                        <div class="feature-desc">D-데이 역산 플랜</div>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 56%"></div>
                </div>
                <button class="cta-button" onclick="nextScene(5)">놀라워! 계속해줘</button>
            </div>

            <!-- Scene 5: Execution -->
            <div class="scene" id="scene5">
                <span class="title-badge">EXECUTION MODE</span>
                <h1>실행이 곧<br><span class="magic-text">결과</span>입니다</h1>
                <div class="typing-text" id="text5"></div>
                <div class="timeline" id="execution-timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker">✅</div>
                        <div class="timeline-content">
                            <div class="timeline-title">자동화된 체크리스트</div>
                            <div class="timeline-desc">놓치는 것 없이 완벽하게</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker">📈</div>
                        <div class="timeline-content">
                            <div class="timeline-title">실시간 진행률 추적</div>
                            <div class="timeline-desc">목표 대비 현재 위치 확인</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker">🔄</div>
                        <div class="timeline-content">
                            <div class="timeline-title">48시간 오답 분석</div>
                            <div class="timeline-desc">빠른 피드백, 확실한 개선</div>
                        </div>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 70%"></div>
                </div>
                <button class="cta-button" onclick="nextScene(6)">이런 시스템이라니!</button>
            </div>

            <!-- Scene 6: Results -->
            <div class="scene" id="scene6">
                <span class="title-badge">YOUR SCORE</span>
                <h1>목표점수<br><span class="magic-text">달성 가능합니다</span></h1>
                <div class="typing-text" id="text6"></div>
                <div class="score-display" id="score">82 → 95</div>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🚀</div>
                        <div class="feature-title">평균 13점 상승</div>
                        <div class="feature-desc">D-30 시스템 적용 학생들</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💯</div>
                        <div class="feature-title">정답률 90% 달성</div>
                        <div class="feature-desc">기출문제 3회독 효과</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⏰</div>
                        <div class="feature-title">시간관리 마스터</div>
                        <div class="feature-desc">실전모의로 체득한 페이스</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🎯</div>
                        <div class="feature-title">약점 제로</div>
                        <div class="feature-desc">집중 보정으로 빈틈 제거</div>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 84%"></div>
                </div>
                <button class="cta-button" onclick="nextScene(7)">나도 할 수 있을까?</button>
            </div>

            <!-- Scene 7: Final -->
            <div class="scene" id="scene7">
                <span class="title-badge">START NOW</span>
                <h1>지금부터<br><span class="magic-text">시작</span>입니다</h1>
                <div class="typing-text" id="text7"></div>
                <div class="timeline" id="final-timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker">1</div>
                        <div class="timeline-content">
                            <div class="timeline-title">시험 정보 입력</div>
                            <div class="timeline-desc">D-day, 범위, 현재 점수</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker">2</div>
                        <div class="timeline-content">
                            <div class="timeline-title">맞춤 플랜 생성</div>
                            <div class="timeline-desc">AI가 분석한 최적 경로</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker">3</div>
                        <div class="timeline-content">
                            <div class="timeline-title">오늘부터 실행</div>
                            <div class="timeline-desc">매일 체크, 매일 성장</div>
                        </div>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 100%"></div>
                </div>
                <button class="cta-button" onclick="startJourney()" style="background: linear-gradient(135deg, #f39c12, #e74c3c); font-size: 18px; padding: 20px 50px;">
                    🚀 나의 D-30 시작하기
                </button>
            </div>
        </div>
    </div>

    <script>
        // Particle generation
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                particlesContainer.appendChild(particle);
            }
        }
        createParticles();

        // Typing effect
        function typeWriter(elementId, text, speed = 30) {
            const element = document.getElementById(elementId);
            element.innerHTML = '';
            let i = 0;
            
            function type() {
                if (i < text.length) {
                    if (text.substring(i, i + 6) === '<span>') {
                        const endIndex = text.indexOf('</span>', i) + 7;
                        element.innerHTML += text.substring(i, endIndex);
                        i = endIndex;
                    } else {
                        element.innerHTML += text.charAt(i);
                        i++;
                    }
                    setTimeout(type, speed);
                } else {
                    // Show button after typing completes
                    const button = element.parentElement.querySelector('.cta-button');
                    if (button) {
                        setTimeout(() => button.classList.add('show'), 200);
                    }
                }
            }
            type();
        }

        // Scene navigation
        let currentScene = 1;
        
        function nextScene(sceneNumber) {
            document.getElementById(`scene${currentScene}`).classList.remove('active');
            currentScene = sceneNumber;
            const newScene = document.getElementById(`scene${sceneNumber}`);
            newScene.classList.add('active');
            
            // Update progress
            const progressFills = document.querySelectorAll('.progress-fill');
            progressFills.forEach(fill => {
                fill.style.width = (sceneNumber * 14) + '%';
            });
            
            // Trigger animations for each scene
            setTimeout(() => loadSceneContent(sceneNumber), 100);
        }

        function loadSceneContent(scene) {
            switch(scene) {
                case 1:
                    typeWriter('text1', 
                        '매년 같은 실수를 반복하시나요?<br>' +
                        '시험 전날 벼락치기에 지치셨나요?<br><br>' +
                        '이제는 다릅니다.<br>' +
                        '<span class="highlight">체계적인 30일</span>이 당신의 점수를 바꿉니다.'
                    );
                    break;
                case 2:
                    typeWriter('text2', 
                        '우리는 시험을 <span class="highlight">역산</span>합니다.<br>' +
                        'D-30부터 매일이 계획되어 있고,<br>' +
                        '매 순간이 점수로 연결됩니다.'
                    );
                    setTimeout(() => {
                        document.getElementById('timeline').classList.add('show');
                        const items = document.querySelectorAll('#timeline .timeline-item');
                        items.forEach((item, index) => {
                            setTimeout(() => item.classList.add('show'), index * 200);
                        });
                    }, 1500);
                    break;
                case 3:
                    typeWriter('text3', 
                        '수많은 학생들이 증명한<br>' +
                        '<span class="highlight">검증된 시스템</span>입니다.'
                    );
                    break;
                case 4:
                    typeWriter('text4', 
                        'W-X-S-P-E-R-T-A<br>' +
                        '<span class="highlight">8개의 지능</span>이 유기적으로 연결되어<br>' +
                        '당신만의 최적화된 학습을 설계합니다.'
                    );
                    break;
                case 5:
                    typeWriter('text5', 
                        '계획은 실행될 때 의미가 있습니다.<br>' +
                        '<span class="highlight">매일 체크, 매일 피드백</span><br>' +
                        '당신은 따라오기만 하면 됩니다.'
                    );
                    setTimeout(() => {
                        const items = document.querySelectorAll('#execution-timeline .timeline-item');
                        items.forEach((item, index) => {
                            setTimeout(() => item.classList.add('show'), index * 200);
                        });
                    }, 1500);
                    break;
                case 6:
                    typeWriter('text6', 
                        '이것은 단순한 약속이 아닙니다.<br>' +
                        '<span class="highlight">데이터가 증명하는 결과</span>입니다.'
                    );
                    setTimeout(() => {
                        document.getElementById('score').classList.add('show');
                    }, 1500);
                    break;
                case 7:
                    typeWriter('text7', 
                        '더 이상 망설이지 마세요.<br>' +
                        '당신의 <span class="highlight">D-30이 지금 시작</span>됩니다.<br><br>' +
                        '시험은 프로젝트, 점수는 결과물.<br>' +
                        '우리가 함께 만들어갑니다.'
                    );
                    setTimeout(() => {
                        const items = document.querySelectorAll('#final-timeline .timeline-item');
                        items.forEach((item, index) => {
                            setTimeout(() => item.classList.add('show'), index * 200);
                        });
                    }, 2000);
                    break;
            }
        }

        function skipToEnd() {
            nextScene(7);
        }

        function startJourney() {
            const card = document.querySelector('.story-card');
            card.style.transform = 'scale(0.95)';
            setTimeout(() => {
                card.innerHTML = `
                    <div style="text-align: center; padding: 60px 20px;">
                        <div style="font-size: 60px; margin-bottom: 20px;">🚀</div>
                        <h1 style="color: #1a1a2e; margin-bottom: 20px;">시작되었습니다!</h1>
                        <p style="color: #666; font-size: 18px; line-height: 1.6;">
                            당신의 D-30 여정이 시작되었습니다.<br>
                            매일 함께하며 목표를 달성하겠습니다.
                        </p>
                        <div style="margin-top: 40px; padding: 20px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 15px;">
                            <p style="color: #2c3e50; font-weight: 600; margin-bottom: 10px;">오늘의 미션</p>
                            <p style="color: #666;">📊 현재 점수 진단 → 📝 약점 리스트 작성 → 🎯 목표 점수 설정</p>
                        </div>
                    </div>
                `;
                card.style.transform = 'scale(1)';
            }, 300);
        }

        // Initialize first scene
        loadSceneContent(1);
    </script>
</body>
</html>