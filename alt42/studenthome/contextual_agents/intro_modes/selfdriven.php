<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>자율 학습형 - Welcome Session</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700;900&display=swap');
        
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
        
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: float 20s infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
        
        .container {
            position: relative;
            z-index: 10;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .story-book {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            width: 90%;
            max-width: 900px;
            height: 85vh;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
        }
        
        .page {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .page.active {
            opacity: 1;
            transform: translateX(0);
        }
        
        .page.prev {
            transform: translateX(-100%);
        }
        
        .page-number {
            position: absolute;
            top: 30px;
            right: 40px;
            color: #999;
            font-size: 14px;
        }
        
        .typing-text {
            font-size: 24px;
            line-height: 1.8;
            color: #333;
            margin-bottom: 30px;
            min-height: 100px;
        }
        
        .typing-text.large {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
        }
        
        .illustration {
            width: 300px;
            height: 300px;
            margin: 30px 0;
            position: relative;
        }
        
        .rocket {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: rocket-float 3s ease-in-out infinite;
        }
        
        @keyframes rocket-float {
            0%, 100% { transform: translate(-50%, -50%) translateY(0); }
            50% { transform: translate(-50%, -50%) translateY(-20px); }
        }
        
        .compass {
            width: 150px;
            height: 150px;
            border: 8px solid #667eea;
            border-radius: 50%;
            position: relative;
            animation: compass-rotate 10s linear infinite;
        }
        
        @keyframes compass-rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .compass::before {
            content: '';
            position: absolute;
            width: 4px;
            height: 60px;
            background: #764ba2;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -100%);
            transform-origin: center bottom;
            animation: needle-swing 2s ease-in-out infinite;
        }
        
        @keyframes needle-swing {
            0%, 100% { transform: translate(-50%, -100%) rotate(0deg); }
            50% { transform: translate(-50%, -100%) rotate(20deg); }
        }
        
        .path-diagram {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 40px 0;
        }
        
        .path-node {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            opacity: 0;
            transform: scale(0);
            animation: pop-in 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
        }
        
        .path-node:nth-child(1) { animation-delay: 0.2s; }
        .path-node:nth-child(2) { animation-delay: 0.4s; }
        .path-node:nth-child(3) { animation-delay: 0.6s; }
        .path-node:nth-child(4) { animation-delay: 0.8s; }
        .path-node:nth-child(5) { animation-delay: 1s; }
        
        @keyframes pop-in {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .path-line {
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            opacity: 0;
            animation: line-appear 0.3s ease forwards;
        }
        
        .path-line:nth-child(2) { animation-delay: 0.3s; }
        .path-line:nth-child(4) { animation-delay: 0.5s; }
        .path-line:nth-child(6) { animation-delay: 0.7s; }
        .path-line:nth-child(8) { animation-delay: 0.9s; }
        
        @keyframes line-appear {
            to { opacity: 1; }
        }
        
        .feature-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            width: 100%;
            margin: 30px 0;
        }
        
        .feature-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 15px;
            padding: 25px;
            opacity: 0;
            transform: translateY(30px);
            animation: slide-up 0.6s ease forwards;
        }
        
        .feature-card:nth-child(1) { animation-delay: 0.2s; }
        .feature-card:nth-child(2) { animation-delay: 0.4s; }
        .feature-card:nth-child(3) { animation-delay: 0.6s; }
        .feature-card:nth-child(4) { animation-delay: 0.8s; }
        
        @keyframes slide-up {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .feature-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .feature-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .navigation {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            z-index: 20;
        }
        
        .nav-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(102, 126, 234, 0.2);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0;
            transition: width 0.8s ease;
        }
        
        .highlight {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .emoji-float {
            position: absolute;
            font-size: 40px;
            animation: emoji-rise 3s ease-out forwards;
            pointer-events: none;
        }
        
        @keyframes emoji-rise {
            0% {
                transform: translateY(0) scale(0);
                opacity: 1;
            }
            50% {
                transform: translateY(-100px) scale(1);
                opacity: 1;
            }
            100% {
                transform: translateY(-200px) scale(0.5);
                opacity: 0;
            }
        }
        
        .loading-dots {
            display: inline-block;
        }
        
        .loading-dots::after {
            content: '...';
            display: inline-block;
            animation: dots 1.5s steps(4, end) infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="container">
        <div class="story-book">
            <!-- Page 1: Opening -->
            <div class="page active" id="page1">
                <span class="page-number">1 / 8</span>
                <div class="typing-text large" id="title1"></div>
                <div class="illustration">
                    <div class="rocket"></div>
                </div>
                <div class="typing-text" id="text1"></div>
            </div>
            
            <!-- Page 2: Your Journey -->
            <div class="page" id="page2">
                <span class="page-number">2 / 8</span>
                <div class="typing-text" id="text2"></div>
                <div class="path-diagram" id="pathDiagram" style="display: none;">
                    <div class="path-node">📝</div>
                    <div class="path-line"></div>
                    <div class="path-node">🎯</div>
                    <div class="path-line"></div>
                    <div class="path-node">🚀</div>
                    <div class="path-line"></div>
                    <div class="path-node">📊</div>
                    <div class="path-line"></div>
                    <div class="path-node">🏆</div>
                </div>
                <div class="typing-text" id="text2b"></div>
            </div>
            
            <!-- Page 3: Core Philosophy -->
            <div class="page" id="page3">
                <span class="page-number">3 / 8</span>
                <div class="typing-text large" id="text3"></div>
                <div class="illustration">
                    <div class="compass"></div>
                </div>
                <div class="typing-text" id="text3b"></div>
            </div>
            
            <!-- Page 4: Your Powers -->
            <div class="page" id="page4">
                <span class="page-number">4 / 8</span>
                <div class="typing-text" id="text4"></div>
                <div class="feature-cards" id="featureCards" style="display: none;">
                    <div class="feature-card">
                        <h3>📅 계획 설계권</h3>
                        <p>주간·월간 학습 계획을 직접 수립하고 커스터마이징</p>
                    </div>
                    <div class="feature-card">
                        <h3>🎨 커리큘럼 자유도</h3>
                        <p>관심사와 목표에 맞춘 나만의 학습 경로 설계</p>
                    </div>
                    <div class="feature-card">
                        <h3>⚡ 속도 조절권</h3>
                        <p>나의 페이스에 맞춘 진도와 난이도 조정</p>
                    </div>
                    <div class="feature-card">
                        <h3>🔄 피드백 주도권</h3>
                        <p>필요할 때 요청하고 반영하는 맞춤형 피드백</p>
                    </div>
                </div>
            </div>
            
            <!-- Page 5: Smart Support -->
            <div class="page" id="page5">
                <span class="page-number">5 / 8</span>
                <div class="typing-text" id="text5"></div>
                <div class="typing-text" id="text5b"></div>
                <div class="typing-text" id="text5c"></div>
            </div>
            
            <!-- Page 6: Adaptive System -->
            <div class="page" id="page6">
                <span class="page-number">6 / 8</span>
                <div class="typing-text" id="text6"></div>
                <div class="typing-text" id="text6b"></div>
            </div>
            
            <!-- Page 7: Your Growth -->
            <div class="page" id="page7">
                <span class="page-number">7 / 8</span>
                <div class="typing-text" id="text7"></div>
                <div class="typing-text" id="text7b"></div>
            </div>
            
            <!-- Page 8: Ready to Start -->
            <div class="page" id="page8">
                <span class="page-number">8 / 8</span>
                <div class="typing-text large" id="text8"></div>
                <div class="typing-text" id="text8b"></div>
                <div class="typing-text" id="text8c"></div>
            </div>
            
            <div class="navigation">
                <button class="nav-btn" id="prevBtn" onclick="previousPage()" disabled>이전</button>
                <button class="nav-btn" id="nextBtn" onclick="nextPage()">다음</button>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
        </div>
    </div>
    
    <script>
        let currentPage = 1;
        const totalPages = 8;
        const typingSpeed = 40;
        let isTyping = false;
        
        // Page content
        const pageContent = {
            1: {
                title: "🚀 자율 학습형 모드",
                text: "드디어 만났네요.\n당신이 찾던 그 학습법.",
                afterTyping: () => createEmoji('🚀', 3)
            },
            2: {
                text: "여기서는 당신이 주인공입니다.",
                afterText: () => {
                    document.getElementById('pathDiagram').style.display = 'flex';
                },
                textB: "계획 수립부터 실행, 평가까지\n모든 과정을 당신이 설계합니다."
            },
            3: {
                text: "학습의 주인은 학생 자신이다",
                textB: "이것이 우리의 핵심 신념입니다.\n\n교사는 가이드, 조력자, 안전망.\n하지만 결정권은 온전히 당신에게."
            },
            4: {
                text: "당신에게 주어진 권한들:",
                afterText: () => {
                    document.getElementById('featureCards').style.display = 'grid';
                    createEmoji('✨', 5);
                }
            },
            5: {
                text: "물론 혼자가 아닙니다.",
                textB: "✅ 계획의 현실성과 품질을 함께 검토\n✅ 실행률이 떨어지면 원인 분석 지원\n✅ 월 2회 심층 피드백 세션",
                textC: "자율성과 지원의 완벽한 균형."
            },
            6: {
                text: "똑똑한 적응형 시스템",
                textB: "📊 실행률 < 70%? → 동기부여 모드 병행\n📚 계획 품질 저조? → 구조화 지원 투입\n✏️ 시험 D-30? → 집중 대비 모드 전환\n🧠 창의 프로젝트? → 사고력 모드 결합\n\n필요할 때, 필요한 만큼만."
            },
            7: {
                text: "당신의 성장 지표",
                textB: "🎯 자율 실행률 80% 이상\n📈 계획 품질 4/5점 이상\n🔄 피드백 반영률 90% 이상\n🏆 목표 달성률 85% 이상\n\n측정 가능한 성장, 눈에 보이는 변화."
            },
            8: {
                text: "준비되셨나요?",
                textB: "이제 당신이 직접 학습을 설계할 시간입니다.",
                textC: "🚀 나만의 학습 여정을 시작하기",
                afterTyping: () => {
                    createEmoji('🎯', 2);
                    createEmoji('📚', 2);
                    createEmoji('💪', 2);
                    document.getElementById('nextBtn').textContent = '시작하기';
                    document.getElementById('nextBtn').style.background = 'linear-gradient(135deg, #f093fb, #f5576c)';
                }
            }
        };
        
        // Initialize particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                particlesContainer.appendChild(particle);
            }
        }
        
        // Typing effect with HTML support
        async function typeText(elementId, text, speed = typingSpeed) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            element.innerHTML = '';
            isTyping = true;
            
            let index = 0;
            while (index < text.length) {
                // HTML 태그 처리
                if (text[index] === '<') {
                    const closeIndex = text.indexOf('>', index);
                    if (closeIndex !== -1) {
                        // 전체 태그를 한번에 추가
                        const tag = text.substring(index, closeIndex + 1);
                        element.innerHTML += tag;
                        index = closeIndex + 1;
                    } else {
                        // 유효하지 않은 태그인 경우 일반 문자로 처리
                        element.innerHTML += text[index];
                        index++;
                        await new Promise(resolve => setTimeout(resolve, speed));
                    }
                } else {
                    // 일반 문자 추가
                    element.innerHTML += text[index];
                    index++;
                    await new Promise(resolve => setTimeout(resolve, speed));
                }
            }
            
            isTyping = false;
        }
        
        // Create floating emoji
        function createEmoji(emoji, count = 1) {
            for (let i = 0; i < count; i++) {
                setTimeout(() => {
                    const emojiEl = document.createElement('div');
                    emojiEl.className = 'emoji-float';
                    emojiEl.textContent = emoji;
                    emojiEl.style.left = (20 + Math.random() * 60) + '%';
                    emojiEl.style.bottom = '100px';
                    document.querySelector('.story-book').appendChild(emojiEl);
                    
                    setTimeout(() => emojiEl.remove(), 3000);
                }, i * 200);
            }
        }
        
        // Load page content
        async function loadPage(pageNum) {
            const content = pageContent[pageNum];
            if (!content) return;
            
            // Type title if exists
            if (content.title) {
                await typeText(`title${pageNum}`, content.title, 60);
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            
            // Type main text
            if (content.text) {
                await typeText(`text${pageNum}`, content.text);
                if (content.afterText) {
                    await new Promise(resolve => setTimeout(resolve, 500));
                    content.afterText();
                }
            }
            
            // Type secondary text
            if (content.textB) {
                await new Promise(resolve => setTimeout(resolve, 800));
                await typeText(`text${pageNum}b`, content.textB);
            }
            
            // Type tertiary text
            if (content.textC) {
                await new Promise(resolve => setTimeout(resolve, 800));
                await typeText(`text${pageNum}c`, content.textC);
            }
            
            // Execute after typing callback
            if (content.afterTyping) {
                content.afterTyping();
            }
        }
        
        // Navigation
        async function nextPage() {
            if (currentPage >= totalPages || isTyping) return;
            
            if (currentPage === totalPages - 1 && document.getElementById('nextBtn').textContent === '시작하기') {
                // Start learning journey
                alert('🚀 자율 학습 여정이 시작됩니다!\n\n첫 번째 과제: 이번 주 학습 계획을 직접 수립해보세요.');
                return;
            }
            
            const currentPageEl = document.getElementById(`page${currentPage}`);
            const nextPageEl = document.getElementById(`page${currentPage + 1}`);
            
            currentPageEl.classList.remove('active');
            currentPageEl.classList.add('prev');
            nextPageEl.classList.add('active');
            
            currentPage++;
            updateProgress();
            updateButtons();
            
            await loadPage(currentPage);
        }
        
        async function previousPage() {
            if (currentPage <= 1 || isTyping) return;
            
            const currentPageEl = document.getElementById(`page${currentPage}`);
            const prevPageEl = document.getElementById(`page${currentPage - 1}`);
            
            currentPageEl.classList.remove('active');
            prevPageEl.classList.remove('prev');
            prevPageEl.classList.add('active');
            
            currentPage--;
            updateProgress();
            updateButtons();
        }
        
        function updateProgress() {
            const progress = (currentPage / totalPages) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
        }
        
        function updateButtons() {
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = currentPage === totalPages && document.getElementById('nextBtn').textContent !== '시작하기';
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') nextPage();
            if (e.key === 'ArrowLeft') previousPage();
        });
        
        // Initialize
        window.onload = async function() {
            createParticles();
            updateProgress();
            await loadPage(1);
        };
    </script>
</body>
</html>