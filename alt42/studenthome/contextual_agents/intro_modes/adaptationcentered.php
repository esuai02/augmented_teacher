<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Math Journey</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700;900&display=swap');
        
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
        
        /* 배경 수학 기호 애니메이션 */
        .math-symbols {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .symbol {
            position: absolute;
            color: rgba(255, 255, 255, 0.1);
            font-size: 2rem;
            animation: float 15s infinite ease-in-out;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }
        
        /* 메인 컨테이너 */
        .story-container {
            position: relative;
            z-index: 10;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .scene {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            padding: 60px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            animation: sceneEnter 0.8s ease-out;
        }
        
        .scene.active {
            display: block;
        }
        
        @keyframes sceneEnter {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* 타이핑 효과 */
        .typing-text {
            font-size: 1.8rem;
            line-height: 2.2;
            color: #2d3748;
            font-weight: 300;
            min-height: 200px;
            margin-bottom: 40px;
        }
        
        .typing-text .highlight {
            color: #667eea;
            font-weight: 700;
            animation: highlightPulse 2s infinite;
        }
        
        @keyframes highlightPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .cursor {
            display: inline-block;
            width: 3px;
            height: 1.2em;
            background: #667eea;
            animation: blink 1s infinite;
            vertical-align: text-bottom;
            margin-left: 2px;
        }
        
        @keyframes blink {
            50% { opacity: 0; }
        }
        
        /* 진단 카드 */
        .diagnosis-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin: 30px 0;
            transform: scale(0);
            animation: cardReveal 0.8s ease-out forwards;
            animation-delay: 0.5s;
        }
        
        @keyframes cardReveal {
            to {
                transform: scale(1);
            }
        }
        
        .diagnosis-item {
            display: flex;
            align-items: center;
            margin: 20px 0;
            opacity: 0;
            animation: slideIn 0.6s ease-out forwards;
        }
        
        .diagnosis-item:nth-child(1) { animation-delay: 0.8s; }
        .diagnosis-item:nth-child(2) { animation-delay: 1.1s; }
        .diagnosis-item:nth-child(3) { animation-delay: 1.4s; }
        .diagnosis-item:nth-child(4) { animation-delay: 1.7s; }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .diagnosis-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.5rem;
        }
        
        /* 로드맵 비주얼 */
        .roadmap-visual {
            position: relative;
            height: 400px;
            margin: 40px 0;
        }
        
        .roadmap-path {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            animation: pathDraw 2s ease-out forwards;
        }
        
        @keyframes pathDraw {
            to {
                opacity: 1;
            }
        }
        
        .milestone {
            position: absolute;
            width: 80px;
            height: 80px;
            background: white;
            border: 3px solid #667eea;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            top: 50%;
            transform: translateY(-50%) scale(0);
            animation: milestoneAppear 0.6s ease-out forwards;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .milestone:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .milestone:nth-child(2) { left: 10%; animation-delay: 0.5s; }
        .milestone:nth-child(3) { left: 30%; animation-delay: 0.8s; }
        .milestone:nth-child(4) { left: 50%; animation-delay: 1.1s; }
        .milestone:nth-child(5) { left: 70%; animation-delay: 1.4s; }
        .milestone:nth-child(6) { left: 90%; animation-delay: 1.7s; }
        
        @keyframes milestoneAppear {
            to {
                transform: translateY(-50%) scale(1);
            }
        }
        
        .milestone-icon {
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .milestone-text {
            font-size: 0.7rem;
            color: #667eea;
            font-weight: 700;
        }
        
        /* 성향 선택 */
        .style-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .style-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
            animation: styleCardAppear 0.6s ease-out forwards;
        }
        
        .style-card:nth-child(1) { animation-delay: 0.3s; }
        .style-card:nth-child(2) { animation-delay: 0.5s; }
        .style-card:nth-child(3) { animation-delay: 0.7s; }
        
        @keyframes styleCardAppear {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .style-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        .style-card.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .style-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        /* 버튼 */
        .action-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .action-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: scale(0);
            animation: rippleEffect 0.6s ease-out;
        }
        
        @keyframes rippleEffect {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        /* 파티클 효과 */
        .particle {
            position: absolute;
            pointer-events: none;
            opacity: 0;
            animation: particleFloat 3s ease-out forwards;
        }
        
        @keyframes particleFloat {
            0% {
                opacity: 1;
                transform: translate(0, 0) scale(0);
            }
            100% {
                opacity: 0;
                transform: translate(var(--x), var(--y)) scale(1);
            }
        }
        
        /* 최종 대시보드 */
        .dashboard-preview {
            background: #f7fafc;
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            opacity: 0;
            transform: scale(0.95);
            animation: dashboardReveal 1s ease-out forwards;
            animation-delay: 0.5s;
        }
        
        @keyframes dashboardReveal {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* 프로그레스 바 */
        .progress-container {
            margin: 30px 0;
            opacity: 0;
            animation: fadeIn 1s ease-out forwards;
            animation-delay: 1s;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        
        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            width: 0;
            animation: progressGrow 2s ease-out forwards;
            animation-delay: 1.5s;
        }
        
        @keyframes progressGrow {
            to { width: var(--progress); }
        }
    </style>
</head>
<body>
    <!-- 배경 수학 기호들 -->
    <div class="math-symbols">
        <div class="symbol" style="top: 10%; left: 10%;">∑</div>
        <div class="symbol" style="top: 20%; left: 80%; animation-delay: 2s;">∫</div>
        <div class="symbol" style="top: 70%; left: 20%; animation-delay: 4s;">π</div>
        <div class="symbol" style="top: 60%; left: 70%; animation-delay: 6s;">√</div>
        <div class="symbol" style="top: 40%; left: 50%; animation-delay: 8s;">∞</div>
        <div class="symbol" style="top: 80%; left: 90%; animation-delay: 10s;">Δ</div>
    </div>
    
    <div class="story-container">
        <!-- Scene 1: 첫 만남 -->
        <div class="scene active" id="scene1">
            <div class="typing-text" id="text1"></div>
            <button class="action-button" onclick="nextScene(2)" disabled id="btn1">
                나만의 지도 받기 →
            </button>
        </div>
        
        <!-- Scene 2: 진단 -->
        <div class="scene" id="scene2">
            <div class="typing-text" id="text2"></div>
            <div class="diagnosis-card" style="display: none;" id="diagCard">
                <div class="diagnosis-item">
                    <div class="diagnosis-icon">📊</div>
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">현재 위치</div>
                        <div style="font-size: 1.3rem; font-weight: 700;">기초 65점</div>
                    </div>
                </div>
                <div class="diagnosis-item">
                    <div class="diagnosis-icon">🎯</div>
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">취약 영역</div>
                        <div style="font-size: 1.3rem; font-weight: 700;">확률, 수열</div>
                    </div>
                </div>
                <div class="diagnosis-item">
                    <div class="diagnosis-icon">⏰</div>
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">집중 패턴</div>
                        <div style="font-size: 1.3rem; font-weight: 700;">오후 7-9시</div>
                    </div>
                </div>
                <div class="diagnosis-item">
                    <div class="diagnosis-icon">🚀</div>
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">목표 달성일</div>
                        <div style="font-size: 1.3rem; font-weight: 700;">D-120</div>
                    </div>
                </div>
            </div>
            <button class="action-button" onclick="nextScene(3)" disabled id="btn2">
                내 학습 스타일 선택하기 →
            </button>
        </div>
        
        <!-- Scene 3: 학습 스타일 선택 -->
        <div class="scene" id="scene3">
            <div class="typing-text" id="text3"></div>
            <div class="style-selector" style="display: none;" id="styleSelector">
                <div class="style-card" onclick="selectStyle(this, 'visual')">
                    <div class="style-icon">👁️</div>
                    <div style="font-weight: 700; margin-bottom: 10px;">시각형</div>
                    <div style="font-size: 0.85rem; color: #718096;">
                        그래프와 도표로<br>이해가 빨라요
                    </div>
                </div>
                <div class="style-card" onclick="selectStyle(this, 'auditory')">
                    <div class="style-icon">👂</div>
                    <div style="font-weight: 700; margin-bottom: 10px;">청각형</div>
                    <div style="font-size: 0.85rem; color: #718096;">
                        설명을 들으면<br>기억이 잘 나요
                    </div>
                </div>
                <div class="style-card" onclick="selectStyle(this, 'kinesthetic')">
                    <div class="style-icon">✍️</div>
                    <div style="font-weight: 700; margin-bottom: 10px;">실습형</div>
                    <div style="font-size: 0.85rem; color: #718096;">
                        직접 풀어봐야<br>이해가 돼요
                    </div>
                </div>
            </div>
            <button class="action-button" onclick="nextScene(4)" disabled id="btn3">
                맞춤 로드맵 생성하기 →
            </button>
        </div>
        
        <!-- Scene 4: 로드맵 생성 -->
        <div class="scene" id="scene4">
            <div class="typing-text" id="text4"></div>
            <div class="roadmap-visual" style="display: none;" id="roadmap">
                <div class="roadmap-path"></div>
                <div class="milestone">
                    <div class="milestone-icon">🌱</div>
                    <div class="milestone-text">기초</div>
                </div>
                <div class="milestone">
                    <div class="milestone-icon">📐</div>
                    <div class="milestone-text">확률</div>
                </div>
                <div class="milestone">
                    <div class="milestone-icon">🔢</div>
                    <div class="milestone-text">수열</div>
                </div>
                <div class="milestone">
                    <div class="milestone-icon">📈</div>
                    <div class="milestone-text">응용</div>
                </div>
                <div class="milestone">
                    <div class="milestone-icon">🏆</div>
                    <div class="milestone-text">목표</div>
                </div>
            </div>
            <div class="progress-container" style="display: none;" id="progress">
                <div style="font-weight: 700; color: #2d3748;">예상 성장 곡선</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="--progress: 85%;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 0.9rem; color: #718096;">
                    <span>현재: 65점</span>
                    <span>목표: 85점 (+20)</span>
                </div>
            </div>
            <button class="action-button" onclick="nextScene(5)" disabled id="btn4">
                학습 시작하기 →
            </button>
        </div>
        
        <!-- Scene 5: 대시보드 미리보기 -->
        <div class="scene" id="scene5">
            <div class="typing-text" id="text5"></div>
            <div class="dashboard-preview" style="display: none;" id="dashboard">
                <div class="stat-card">
                    <div class="stat-label">주간 학습시간</div>
                    <div class="stat-value">14h</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">약점 개선율</div>
                    <div class="stat-value">+23%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">맞춤 문제 풀이</div>
                    <div class="stat-value">127개</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">성취 뱃지</div>
                    <div class="stat-value">🏅 × 5</div>
                </div>
            </div>
            <button class="action-button" onclick="startJourney()" id="btn5" style="display: none;">
                🚀 나만의 수학 여정 시작하기
            </button>
        </div>
    </div>
    
    <script>
        let selectedStyle = null;
        let currentScene = 1;
        
        // 타이핑 효과 함수 (HTML 태그 완전 지원)
        function typeText(elementId, text, callback) {
            const element = document.getElementById(elementId);
            element.innerHTML = '';
            let index = 0;
            
            function type() {
                if (index < text.length) {
                    // HTML 태그 시작 감지
                    if (text[index] === '<') {
                        const closeIndex = text.indexOf('>', index);
                        if (closeIndex !== -1) {
                            // 전체 태그를 한번에 추가
                            const currentContent = text.substring(0, closeIndex + 1);
                            element.innerHTML = currentContent;
                            index = closeIndex + 1;
                        } else {
                            // 유효하지 않은 태그인 경우 일반 문자로 처리
                            element.innerHTML = text.substring(0, index + 1);
                            index++;
                        }
                    } else {
                        // 일반 문자 추가
                        element.innerHTML = text.substring(0, index + 1);
                        index++;
                    }
                    
                    // 커서 추가 (타이핑 중에만)
                    element.innerHTML += '<span class="cursor"></span>';
                    
                    setTimeout(type, 30);
                } else {
                    // 타이핑 완료 - 커서 제거
                    element.innerHTML = text;
                    if (callback) callback();
                }
            }
            
            type();
        }
        
        // Scene 전환
        function nextScene(sceneNum) {
            // 리플 효과
            event.target.innerHTML += '<span class="ripple"></span>';
            
            setTimeout(() => {
                document.getElementById(`scene${sceneNum - 1}`).classList.remove('active');
                setTimeout(() => {
                    document.getElementById(`scene${sceneNum}`).classList.add('active');
                    initScene(sceneNum);
                }, 300);
            }, 300);
        }
        
        // 각 Scene 초기화
        function initScene(sceneNum) {
            switch(sceneNum) {
                case 1:
                    typeText('text1', 
                        '안녕하세요! 👋<br><br>' +
                        '지금까지 <span class="highlight">모두가 같은 방법</span>으로 수학을 공부하셨나요?<br><br>' +
                        '이제 <span class="highlight">당신만의 지도</span>를 들고<br>' +
                        '당신만의 속도로 걸어갈 시간입니다.<br><br>' +
                        '🎯 개인 맞춤형 학습이 시작됩니다.',
                        () => {
                            document.getElementById('btn1').disabled = false;
                        }
                    );
                    break;
                    
                case 2:
                    typeText('text2',
                        '먼저, <span class="highlight">당신이 어디에 있는지</span> 알아볼게요.<br><br>' +
                        'AI가 분석한 당신의 현재 위치입니다:',
                        () => {
                            setTimeout(() => {
                                document.getElementById('diagCard').style.display = 'block';
                                setTimeout(() => {
                                    document.getElementById('btn2').disabled = false;
                                }, 2000);
                            }, 500);
                        }
                    );
                    break;
                    
                case 3:
                    typeText('text3',
                        '당신은 <span class="highlight">어떤 방식</span>으로 가장 잘 배우시나요?<br><br>' +
                        '학습 스타일을 선택하면<br>' +
                        '그에 맞는 자료와 설명 방식을 제공해드려요.',
                        () => {
                            setTimeout(() => {
                                document.getElementById('styleSelector').style.display = 'grid';
                            }, 500);
                        }
                    );
                    break;
                    
                case 4:
                    typeText('text4',
                        `<span class="highlight">${selectedStyle === 'visual' ? '시각형' : selectedStyle === 'auditory' ? '청각형' : '실습형'}</span> 학습자시군요!<br><br>` +
                        '당신만을 위한 <span class="highlight">맞춤 로드맵</span>을 생성했습니다.<br>' +
                        '각 단계마다 당신의 속도에 맞춰 진행됩니다.',
                        () => {
                            setTimeout(() => {
                                document.getElementById('roadmap').style.display = 'block';
                                setTimeout(() => {
                                    document.getElementById('progress').style.display = 'block';
                                    setTimeout(() => {
                                        document.getElementById('btn4').disabled = false;
                                    }, 1500);
                                }, 1500);
                            }, 500);
                        }
                    );
                    break;
                    
                case 5:
                    typeText('text5',
                        '🎉 <span class="highlight">준비가 완료되었습니다!</span><br><br>' +
                        '매주 당신의 성장을 추적하고<br>' +
                        '실시간으로 학습 전략을 조정합니다.<br><br>' +
                        '이제 정말로 <span class="highlight">당신만의 수학 여정</span>이 시작됩니다.',
                        () => {
                            setTimeout(() => {
                                document.getElementById('dashboard').style.display = 'grid';
                                setTimeout(() => {
                                    document.getElementById('btn5').style.display = 'block';
                                }, 1000);
                            }, 500);
                        }
                    );
                    break;
            }
        }
        
        // 학습 스타일 선택
        function selectStyle(element, style) {
            document.querySelectorAll('.style-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.classList.add('selected');
            selectedStyle = style;
            
            // 파티클 효과
            createParticles(element);
            
            document.getElementById('btn3').disabled = false;
        }
        
        // 파티클 생성
        function createParticles(element) {
            const rect = element.getBoundingClientRect();
            const colors = ['#667eea', '#764ba2', '#f687b3', '#fbb6ce'];
            
            for (let i = 0; i < 12; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = rect.left + rect.width / 2 + 'px';
                particle.style.top = rect.top + rect.height / 2 + 'px';
                particle.style.width = '10px';
                particle.style.height = '10px';
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                particle.style.borderRadius = '50%';
                particle.style.setProperty('--x', (Math.random() - 0.5) * 200 + 'px');
                particle.style.setProperty('--y', (Math.random() - 0.5) * 200 + 'px');
                document.body.appendChild(particle);
                
                setTimeout(() => particle.remove(), 3000);
            }
        }
        
        // 여정 시작
        function startJourney() {
            // 최종 애니메이션
            document.body.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f687b3 100%)';
            
            // 축하 파티클
            for (let i = 0; i < 30; i++) {
                setTimeout(() => {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.innerHTML = ['🎯', '📚', '✨', '🚀', '💫'][Math.floor(Math.random() * 5)];
                    particle.style.left = Math.random() * window.innerWidth + 'px';
                    particle.style.top = window.innerHeight + 'px';
                    particle.style.fontSize = '2rem';
                    particle.style.setProperty('--x', (Math.random() - 0.5) * 100 + 'px');
                    particle.style.setProperty('--y', -window.innerHeight + 'px');
                    document.body.appendChild(particle);
                    
                    setTimeout(() => particle.remove(), 3000);
                }, i * 100);
            }
            
            setTimeout(() => {
                alert('🎯 개인 맞춤형 학습 모드가 활성화되었습니다!\n\n이제 당신만의 속도로, 당신만의 방법으로 수학을 정복해보세요!');
            }, 2000);
        }
        
        // 초기화
        window.onload = () => {
            initScene(1);
        };
    </script>
</body>
</html>