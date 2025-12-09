<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔭 호기심 탐구자의 여정</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: white;
            overflow: hidden;
            height: 100vh;
            position: relative;
        }
        
        /* 별 배경 애니메이션 */
        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
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
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }
        
        /* 메인 컨테이너 */
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
            z-index: 10;
        }
        
        .scene {
            position: absolute;
            width: 90%;
            max-width: 800px;
            text-align: center;
            opacity: 0;
            transform: translateY(20px);
            transition: all 1s ease;
        }
        
        .scene.active {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* 타이핑 효과 */
        .typing-text {
            font-size: 1.8em;
            line-height: 1.6;
            margin: 20px 0;
            min-height: 100px;
            font-weight: 300;
        }
        
        .typing-cursor {
            display: inline-block;
            width: 3px;
            height: 1.2em;
            background: #00ffff;
            animation: blink 1s infinite;
            vertical-align: text-bottom;
            margin-left: 2px;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        /* 질문 버블 */
        .question-bubble {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(0, 255, 255, 0.5);
            border-radius: 20px;
            padding: 15px 30px;
            margin: 10px;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            animation: float 3s ease-in-out infinite;
        }
        
        .question-bubble:hover {
            background: rgba(0, 255, 255, 0.2);
            transform: scale(1.1);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        /* 망원경 아이콘 */
        .telescope {
            font-size: 5em;
            margin: 20px 0;
            animation: rotate 10s linear infinite;
            display: inline-block;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* 진행 버튼 */
        .next-btn {
            background: linear-gradient(45deg, #00ffff, #0099ff);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 1.1em;
            border-radius: 50px;
            cursor: pointer;
            margin-top: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 255, 255, 0.3);
            font-weight: 700;
        }
        
        .next-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 255, 255, 0.5);
        }
        
        .next-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        /* 컴퍼스 네비게이션 */
        .compass-nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .compass-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 20px;
            width: 180px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .compass-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.05);
            border-color: #00ffff;
        }
        
        .compass-item h3 {
            color: #00ffff;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        
        .compass-item p {
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        /* 프로그레스 바 */
        .progress-container {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            z-index: 100;
        }
        
        .progress-dots {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .dot.active {
            background: #00ffff;
            box-shadow: 0 0 10px #00ffff;
        }
        
        /* 플로팅 질문들 */
        .floating-questions {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .floating-q {
            position: absolute;
            color: rgba(0, 255, 255, 0.3);
            font-size: 1.2em;
            animation: float-up 10s linear infinite;
        }
        
        @keyframes float-up {
            from {
                transform: translateY(100vh);
                opacity: 0;
            }
            10% {
                opacity: 0.5;
            }
            90% {
                opacity: 0.5;
            }
            to {
                transform: translateY(-100px);
                opacity: 0;
            }
        }
        
        /* 챕터 타이틀 */
        .chapter-title {
            font-size: 0.9em;
            color: #00ffff;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 30px;
            opacity: 0.7;
        }
        
        /* 하이라이트 텍스트 */
        .highlight {
            color: #00ffff;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
        }
        
        /* 입력 필드 */
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 10px;
            padding: 15px;
            font-size: 1.1em;
            color: white;
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #00ffff;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }
        
        /* 특별 효과 */
        .sparkle {
            animation: sparkle 2s ease-in-out infinite;
        }
        
        @keyframes sparkle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .pulse {
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* 특별 카드 */
        .special-card {
            background: linear-gradient(135deg, rgba(0, 255, 255, 0.1), rgba(0, 153, 255, 0.1));
            border: 2px solid #00ffff;
            border-radius: 20px;
            padding: 30px;
            margin: 30px auto;
            max-width: 600px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <!-- 별 배경 -->
    <div class="stars" id="stars"></div>
    
    <!-- 플로팅 질문들 -->
    <div class="floating-questions" id="floatingQuestions"></div>
    
    <!-- Scene 1: 인트로 -->
    <div class="scene active" id="scene1">
        <div class="chapter-title">Chapter 0. 발견</div>
        <div class="telescope">🔭</div>
        <div class="typing-text" id="intro-text"></div>
        <button class="next-btn" id="btn1" disabled onclick="nextScene(2)">나의 호기심 확인하기</button>
    </div>
    
    <!-- Scene 2: 질문의 힘 -->
    <div class="scene" id="scene2">
        <div class="chapter-title">Chapter 1. 질문의 힘</div>
        <div class="typing-text" id="question-text"></div>
        <div id="question-bubbles" style="margin: 30px 0;"></div>
        <button class="next-btn" id="btn2" disabled onclick="nextScene(3)">나도 질문하고 싶어!</button>
    </div>
    
    <!-- Scene 3: 첫 질문 만들기 -->
    <div class="scene" id="scene3">
        <div class="chapter-title">Chapter 2. 첫 번째 질문</div>
        <div class="typing-text" id="first-question-text"></div>
        <input type="text" class="input-field" id="userQuestion" placeholder="예: 왜 수학에는 음수가 있을까?" onkeyup="checkQuestion()">
        <button class="next-btn" id="btn3" disabled onclick="nextScene(4)">질문 등록하기</button>
    </div>
    
    <!-- Scene 4: W-X-S-P-E-R-T-A 에이전트 시스템 -->
    <div class="scene" id="scene4">
        <div class="chapter-title">Chapter 3. 탐구 에이전트 시스템</div>
        <div class="typing-text" id="compass-text"></div>
        <div class="compass-nav" id="compass-nav"></div>
        <button class="next-btn" id="btn4" disabled onclick="nextScene(5)">에이전트와 탐구하기</button>
    </div>
    
    <!-- Scene 5: KPI 추적 시스템 -->
    <div class="scene" id="scene5">
        <div class="chapter-title">Chapter 4. 탐구 성과 측정</div>
        <div class="special-card">
            <div class="typing-text" id="kpi-text"></div>
            <div id="kpi-metrics" style="margin-top: 30px;"></div>
        </div>
        <button class="next-btn" id="btn5" disabled onclick="nextScene(6)">KPI 확인하고 계속하기</button>
    </div>
    
    <!-- Scene 6: 학습 철학 -->
    <div class="scene" id="scene6">
        <div class="chapter-title">Chapter 5. 우리의 약속</div>
        <div class="special-card">
            <div class="typing-text" id="philosophy-text"></div>
        </div>
        <button class="next-btn" id="btn6" disabled onclick="nextScene(7)">동의하고 시작하기</button>
    </div>
    
    <!-- Scene 7: 시작 -->
    <div class="scene" id="scene7">
        <div class="chapter-title">Chapter ∞. 무한한 탐구</div>
        <div class="typing-text" id="final-text"></div>
        <div id="final-animation" style="margin: 40px 0;"></div>
        <button class="next-btn pulse" id="btn7" disabled onclick="startJourney()">
            🚀 탐구 여정 시작하기
        </button>
    </div>
    
    <!-- 프로그레스 인디케이터 -->
    <div class="progress-container">
        <div class="progress-dots">
            <div class="dot active" id="dot1"></div>
            <div class="dot" id="dot2"></div>
            <div class="dot" id="dot3"></div>
            <div class="dot" id="dot4"></div>
            <div class="dot" id="dot5"></div>
            <div class="dot" id="dot6"></div>
            <div class="dot" id="dot7"></div>
        </div>
    </div>
    
    <script>
        // 별 생성
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
        
        // 플로팅 질문 생성
        function createFloatingQuestions() {
            const questions = [
                "왜?", "어떻게?", "만약에?", "정말로?", 
                "그래서?", "언제부터?", "누가?", "어디서?"
            ];
            const container = document.getElementById('floatingQuestions');
            
            setInterval(() => {
                const q = document.createElement('div');
                q.className = 'floating-q';
                q.textContent = questions[Math.floor(Math.random() * questions.length)];
                q.style.left = Math.random() * 100 + '%';
                q.style.animationDuration = (Math.random() * 5 + 10) + 's';
                container.appendChild(q);
                
                setTimeout(() => q.remove(), 15000);
            }, 2000);
        }
        
        // 타이핑 효과
        function typeWriter(elementId, text, callback) {
            const element = document.getElementById(elementId);
            element.innerHTML = '';
            let i = 0;
            let isHighlight = false;
            let highlightBuffer = '';
            
            function type() {
                if (i < text.length) {
                    if (text.substr(i, 2) === '\n\n') {
                        element.innerHTML += '<br><br>';
                        i += 2;
                    } else if (text.substr(i, 1) === '\n') {
                        element.innerHTML += '<br>';
                        i += 1;
                    } else if (text.substr(i, 2) === '**') {
                        if (!isHighlight) {
                            isHighlight = true;
                            highlightBuffer = '';
                        } else {
                            element.innerHTML += `<span class="highlight">${highlightBuffer}</span>`;
                            isHighlight = false;
                            highlightBuffer = '';
                        }
                        i += 2;
                    } else {
                        if (isHighlight) {
                            highlightBuffer += text.charAt(i);
                        } else {
                            element.innerHTML += text.charAt(i);
                        }
                        i++;
                    }
                    setTimeout(type, 30);
                } else {
                    element.innerHTML += '<span class="typing-cursor"></span>';
                    if (callback) callback();
                }
            }
            
            type();
        }
        
        // 씬 전환
        function nextScene(sceneNum) {
            // 현재 씬 페이드아웃
            document.querySelectorAll('.scene').forEach(s => s.classList.remove('active'));
            
            // 다음 씬 페이드인
            setTimeout(() => {
                document.getElementById(`scene${sceneNum}`).classList.add('active');
                
                // 프로그레스 업데이트
                document.querySelectorAll('.dot').forEach((d, i) => {
                    d.classList.toggle('active', i < sceneNum);
                });
                
                // 씬별 애니메이션 시작
                startSceneAnimation(sceneNum);
            }, 500);
        }
        
        // 씬별 애니메이션
        function startSceneAnimation(sceneNum) {
            switch(sceneNum) {
                case 1:
                    typeWriter('intro-text', 
                        '안녕! 나는 너의 **호기심 탐구 가이드**야.\n\n' +
                        '시험, 점수, 성적... 이런 건 잠시 잊어버려.\n' +
                        '우리는 오직 **"왜?"**라는 질문으로 시작할 거야.',
                        () => document.getElementById('btn1').disabled = false
                    );
                    break;
                    
                case 2:
                    typeWriter('question-text',
                        '수학의 모든 위대한 발견은 **단순한 질문**에서 시작됐어.\n\n' +
                        '예를 들어볼까?',
                        () => {
                            showQuestionBubbles();
                            setTimeout(() => {
                                document.getElementById('btn2').disabled = false;
                            }, 2000);
                        }
                    );
                    break;
                    
                case 3:
                    typeWriter('first-question-text',
                        '이제 네 차례야!\n\n' +
                        '지금 수학에서 **가장 궁금한 게** 뭐야?\n' +
                        '아주 작은 호기심도 좋아. 시작이 중요하거든.',
                        null
                    );
                    break;
                    
                case 4:
                    const userQ = document.getElementById('userQuestion').value || "수학의 비밀";
                    typeWriter('compass-text',
                        `좋아! "**${userQ}**"\n\n` +
                        '이제 우리의 탐구 컴퍼스를 확인해보자.\n' +
                        '네가 원하는 방향으로 자유롭게 탐구할 수 있어.',
                        () => {
                            showCompassItems();
                            setTimeout(() => {
                                document.getElementById('btn4').disabled = false;
                            }, 1500);
                        }
                    );
                    break;
                    
                case 5:
                    typeWriter('kpi-text',
                        '우리는 너의 탐구 성장을 **정확히 측정**해!\n\n' +
                        '다음 4가지 KPI로 너의 호기심 지수를 추적할 거야.',
                        () => {
                            showKPIMetrics();
                            setTimeout(() => {
                                document.getElementById('btn5').disabled = false;
                            }, 2000);
                        }
                    );
                    break;
                    
                case 6:
                    typeWriter('philosophy-text',
                        '**🌟 호기심 탐구자의 약속**\n\n' +
                        '✨ 점수보다 **질문**이 중요해\n' +
                        '✨ 정답보다 **과정**이 값져\n' +
                        '✨ 실패는 **새로운 시작**이야\n' +
                        '✨ 모든 "왜?"는 **보물**이야\n\n' +
                        '우리는 함께 탐구하는 동료야.',
                        () => document.getElementById('btn6').disabled = false
                    );
                    break;
                    
                case 7:
                    typeWriter('final-text',
                        '자, 이제 정말 시작이야!\n\n' +
                        '네 앞에는 **무한한 질문의 우주**가 펼쳐져 있어.\n' +
                        '매일 새로운 발견, 매일 새로운 "왜?"\n\n' +
                        '준비됐어?',
                        () => {
                            showFinalAnimation();
                            setTimeout(() => {
                                document.getElementById('btn7').disabled = false;
                            }, 2000);
                        }
                    );
                    break;
            }
        }
        
        // 질문 버블 표시
        function showQuestionBubbles() {
            const bubbles = [
                "왜 0으로 나눌 수 없을까?",
                "음수 × 음수 = 양수인 이유는?",
                "무한대는 정말 끝이 없을까?",
                "원주율은 왜 무한히 계속될까?"
            ];
            
            const container = document.getElementById('question-bubbles');
            container.innerHTML = '';
            
            bubbles.forEach((q, i) => {
                setTimeout(() => {
                    const bubble = document.createElement('div');
                    bubble.className = 'question-bubble';
                    bubble.textContent = q;
                    bubble.style.animationDelay = i * 0.2 + 's';
                    container.appendChild(bubble);
                }, i * 300);
            });
        }
        
        // W-X-S-P-E-R-T-A 에이전트 표시
        function showCompassItems() {
            const items = [
                { title: "CuriosityCompass", desc: "🧭 호기심 탐구 경로 설계자\n관련 개념·연계 분야 제시" },
                { title: "QuestionExpander", desc: "❓ 질문 확장 전문가\n새로운 탐구 질문 3~5개 생성" },
                { title: "PortfolioUpdater", desc: "📚 탐구 포트폴리오 관리자\n질문·결과·연계 개념 정리" },
                { title: "GPT Integration", desc: "🤖 즉시 탐구 지원\nGPT 활용도 ≥90% 목표" }
            ];
            
            const container = document.getElementById('compass-nav');
            container.innerHTML = '';
            
            items.forEach((item, i) => {
                setTimeout(() => {
                    const div = document.createElement('div');
                    div.className = 'compass-item';
                    
                    const h3 = document.createElement('h3');
                    h3.textContent = item.title;
                    h3.style.color = '#00ffff';
                    h3.style.fontSize = '1.1em';
                    
                    const p = document.createElement('p');
                    p.innerHTML = item.desc.replace(/\n/g, '<br>');
                    p.style.fontSize = '0.85em';
                    p.style.lineHeight = '1.4';
                    
                    div.appendChild(h3);
                    div.appendChild(p);
                    container.appendChild(div);
                }, i * 200);
            });
        }
        
        // KPI 지표 표시
        function showKPIMetrics() {
            const metrics = [
                { icon: "📊", title: "주간 탐구 질문", target: "≥3개", desc: "매주 생성하는 호기심 질문 수" },
                { icon: "✅", title: "탐구 완료", target: "≥1개/주", desc: "완전히 탐구한 주제 개수" },
                { icon: "🎯", title: "방향 결정률", target: "≥80%", desc: "커리큘럼 지도 기반 정확한 방향 설정" },
                { icon: "🤖", title: "GPT 활용도", target: "≥90%", desc: "GPT를 통한 효과적인 탐구 활용" }
            ];
            
            const container = document.getElementById('kpi-metrics');
            container.innerHTML = '';
            
            // KPI 그리드 스타일 추가
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(2, 1fr)';
            container.style.gap = '15px';
            
            metrics.forEach((metric, i) => {
                setTimeout(() => {
                    const div = document.createElement('div');
                    div.style.background = 'rgba(0, 255, 255, 0.1)';
                    div.style.border = '1px solid rgba(0, 255, 255, 0.3)';
                    div.style.borderRadius = '10px';
                    div.style.padding = '15px';
                    div.style.textAlign = 'center';
                    
                    const icon = document.createElement('div');
                    icon.textContent = metric.icon;
                    icon.style.fontSize = '2em';
                    icon.style.marginBottom = '10px';
                    
                    const title = document.createElement('div');
                    title.textContent = metric.title;
                    title.style.color = '#00ffff';
                    title.style.fontWeight = 'bold';
                    title.style.marginBottom = '5px';
                    
                    const target = document.createElement('div');
                    target.textContent = metric.target;
                    target.style.fontSize = '1.2em';
                    target.style.color = '#00ffff';
                    target.style.marginBottom = '8px';
                    
                    const desc = document.createElement('div');
                    desc.textContent = metric.desc;
                    desc.style.fontSize = '0.8em';
                    desc.style.opacity = '0.8';
                    desc.style.lineHeight = '1.3';
                    
                    div.appendChild(icon);
                    div.appendChild(title);
                    div.appendChild(target);
                    div.appendChild(desc);
                    
                    container.appendChild(div);
                }, i * 200);
            });
        }
        
        // 최종 애니메이션
        function showFinalAnimation() {
            const container = document.getElementById('final-animation');
            container.innerHTML = '';
            
            const equation = document.createElement('div');
            equation.style.fontSize = '4em';
            equation.style.animation = 'pulse 2s infinite';
            equation.textContent = '🔭 + 🤔 = ✨';
            
            const description = document.createElement('div');
            description.style.marginTop = '20px';
            description.style.fontSize = '1.2em';
            description.style.opacity = '0.8';
            description.textContent = '망원경 + 호기심 = 무한한 발견';
            
            container.appendChild(equation);
            container.appendChild(description);
        }
        
        // 질문 입력 체크
        function checkQuestion() {
            const input = document.getElementById('userQuestion');
            const btn = document.getElementById('btn3');
            btn.disabled = input.value.trim().length < 3;
        }
        
        // 여정 시작
        function startJourney() {
            // 실제 학습 시스템으로 전환
            alert('🚀 호기심 탐구 여정이 시작됩니다!\n\n당신의 첫 질문: ' + 
                  (document.getElementById('userQuestion').value || '수학의 비밀을 찾아서'));
        }
        
        // 초기화
        window.onload = function() {
            createStars();
            createFloatingQuestions();
            startSceneAnimation(1);
        };
    </script>
</body>
</html>