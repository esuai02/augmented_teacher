<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧠 사고력 중심 학습 - 사고의 여정</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* 배경 파티클 효과 */
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
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 15s infinite;
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
            width: 90%;
            max-width: 900px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px;
            position: relative;
            z-index: 10;
            backdrop-filter: blur(10px);
            max-height: 90vh;
            overflow-y: auto;
        }

        /* 진행 바 */
        .progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(to right, #667eea, #764ba2);
            transition: width 0.5s ease;
            border-radius: 30px 30px 0 0;
        }

        .scene {
            display: none;
            animation: fadeIn 0.8s ease-out;
            min-height: 500px;
            position: relative;
        }

        .scene.active {
            display: block;
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

        .title {
            font-size: 2.5em;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
            text-align: center;
        }

        .subtitle {
            font-size: 1.3em;
            color: #4a5568;
            margin-bottom: 40px;
            line-height: 1.6;
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.5s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(-10px);
            }
        }

        .content {
            font-size: 1.1em;
            color: #2d3748;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .highlight {
            background: linear-gradient(180deg, transparent 60%, rgba(102, 126, 234, 0.3) 60%);
            font-weight: 600;
            padding: 0 4px;
        }

        /* 입력 필드 */
        .input-field {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 1.1em;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: white;
        }

        .input-field:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .textarea-field {
            resize: vertical;
            min-height: 100px;
        }

        /* 스토리 카드 */
        .story-card {
            background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%);
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transform: scale(0.95);
            opacity: 0;
            animation: cardReveal 0.8s ease-out 1s forwards;
        }

        @keyframes cardReveal {
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            margin: 0 auto 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* 버튼 스타일 */
        .btn-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        /* 시스템 그리드 */
        .system-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .system-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
            animation: slideInUp 0.5s ease-out forwards;
        }

        .system-card:nth-child(1) { animation-delay: 0.1s; }
        .system-card:nth-child(2) { animation-delay: 0.2s; }
        .system-card:nth-child(3) { animation-delay: 0.3s; }
        .system-card:nth-child(4) { animation-delay: 0.4s; }
        .system-card:nth-child(5) { animation-delay: 0.5s; }
        .system-card:nth-child(6) { animation-delay: 0.6s; }
        .system-card:nth-child(7) { animation-delay: 0.7s; }
        .system-card:nth-child(8) { animation-delay: 0.8s; }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .system-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .system-icon {
            font-size: 2em;
            margin-bottom: 10px;
            color: #667eea;
        }

        .system-title {
            font-size: 1.5em;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .system-desc {
            font-size: 0.8em;
            color: #718096;
            line-height: 1.4;
        }

        /* 전략 카드 */
        .strategy-container {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 20px 0;
            margin: 30px 0;
        }

        .strategy-card {
            min-width: 250px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            transform: scale(0.9);
            animation: cardPop 0.5s ease-out forwards;
        }

        .strategy-card:nth-child(1) { animation-delay: 0.1s; }
        .strategy-card:nth-child(2) { animation-delay: 0.2s; }
        .strategy-card:nth-child(3) { animation-delay: 0.3s; }
        .strategy-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes cardPop {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .strategy-card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }

        .strategy-level {
            color: #f59e0b;
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .strategy-title {
            font-size: 1.2em;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .strategy-desc {
            color: #718096;
            font-size: 0.9em;
            line-height: 1.4;
        }

        /* 메타인지 체크리스트 */
        .checklist-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
        }

        .checklist-title {
            font-size: 1.3em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
            color: #4a5568;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .checklist-item:hover {
            color: #2d3748;
        }

        .checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e0;
            border-radius: 4px;
            margin-right: 15px;
            position: relative;
            transition: all 0.3s ease;
        }

        .checkbox.checked {
            background: #667eea;
            border-color: #667eea;
        }

        .checkbox.checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 0.8em;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* 사고 버블 */
        .thought-bubble {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .container {
                padding: 30px;
                max-height: 95vh;
            }

            .title {
                font-size: 1.8em;
            }

            .subtitle {
                font-size: 1.1em;
            }

            .system-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .strategy-container {
                flex-direction: column;
            }

            .strategy-card {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- 파티클 배경 -->
    <div class="particles" id="particles"></div>

    <div class="container">
        <!-- 진행 바 -->
        <div class="progress-bar" id="progressBar"></div>

        <!-- Scene 0: 오프닝 -->
        <div class="scene active" id="scene0">
            <div class="icon-box">🧠</div>
            <h1 class="title">사고력 트레이너</h1>
            
            <p class="subtitle">
                안녕! 나는 너의 사고력 트레이너야.<br>
                오늘부터 너와 함께 <span class="highlight">'생각하는 방법'</span>을 훈련할 거야.<br>
                정답보다 중요한 건... '어떻게 거기까지 갔는가'거든.
            </p>

            <div class="btn-container">
                <button class="btn btn-primary" onclick="nextScene()">
                    시작하기 →
                </button>
            </div>
        </div>

        <!-- Scene 1: 이름 묻기 -->
        <div class="scene" id="scene1">
            <h1 class="title">너를 알고 싶어</h1>
            
            <p class="subtitle">
                먼저 네 이름을 알려줄래?<br><br>
                우리가 함께 만들어갈 사고력 포트폴리오에<br>
                네 이름을 새겨넣고 싶어.
            </p>

            <input type="text" id="userName" class="input-field" placeholder="네 이름을 입력해줘..." 
                   onkeypress="if(event.key==='Enter') nextScene()">

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">다음 →</button>
            </div>
        </div>

        <!-- Scene 2: 질문 던지기 -->
        <div class="scene" id="scene2">
            <h1 class="title">사고의 시작</h1>
            
            <div id="nameGreeting" class="subtitle"></div>

            <div class="story-card">
                <p style="font-size: 1.2em; margin-bottom: 20px;">
                    자, 여기 간단한 질문이 있어:
                </p>
                <p style="font-size: 1.3em; color: #667eea; font-weight: 700; text-align: center;">
                    '왜 공부를 잘하는 학생들은 문제를 빨리 풀까?'
                </p>
                <p style="margin-top: 20px;">
                    네 생각을 자유롭게 말해봐.
                </p>
            </div>

            <textarea id="userThought" class="input-field textarea-field" 
                      placeholder="네 생각을 자유롭게 적어봐..."></textarea>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">내 생각 제출 →</button>
            </div>
        </div>

        <!-- Scene 3: 사고 과정 시각화 -->
        <div class="scene" id="scene3">
            <h1 class="title">훌륭한 사고!</h1>
            
            <p class="subtitle">
                훌륭해! 방금 네가 보여준 게 바로 <span class="highlight">'사고 과정'</span>이야.<br><br>
                🧠 사고력 중심 학습에서는<br>
                이런 생각의 흐름을 포착하고, 정리하고, 발전시켜.
            </p>

            <div id="thoughtDisplay" class="thought-bubble" style="display: none;">
                <h3 style="color: #667eea; margin-bottom: 10px;">💭 네 생각:</h3>
                <p id="thoughtContent"></p>
            </div>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">W-X-S-P-E-R-T-A 시스템 보기 →</button>
            </div>
        </div>

        <!-- Scene 4: W-X-S-P-E-R-T-A 소개 -->
        <div class="scene" id="scene4">
            <h1 class="title">8개의 지능 시스템</h1>
            
            <p class="subtitle">
                우리의 학습 시스템은 8개의 지능으로 구성돼 있어.<br>
                각각이 너의 사고력을 다른 방향으로 확장시켜줄 거야.
            </p>

            <div class="system-grid">
                <div class="system-card">
                    <div class="system-icon">🧭</div>
                    <div class="system-title">W</div>
                    <div class="system-desc">세계관 정렬</div>
                </div>
                <div class="system-card">
                    <div class="system-icon">🧠</div>
                    <div class="system-title">X</div>
                    <div class="system-desc">문맥 지능</div>
                </div>
                <div class="system-card">
                    <div class="system-icon">🗺️</div>
                    <div class="system-title">S</div>
                    <div class="system-desc">구조 지능</div>
                </div>
                <div class="system-card">
                    <div class="system-icon">🎯</div>
                    <div class="system-title">P</div>
                    <div class="system-desc">절차 지능</div>
                </div>
                <div class="system-card">
                    <div class="system-icon">⚡</div>
                    <div class="system-title">E</div>
                    <div class="system-desc">실행 지능</div>
                </div>
                <div class="system-card">
                    <div class="system-icon">💬</div>
                    <div class="system-title">R</div>
                    <div class="system-desc">성찰 지능</div>
                </div>
                <div class="system-card">
                    <div class="system-icon">📈</div>
                    <div class="system-title">T</div>
                    <div class="system-desc">트래픽 지능</div>
                </div>
                <div class="system-card">
                    <div class="system-icon">🚀</div>
                    <div class="system-title">A</div>
                    <div class="system-desc">추상화 지능</div>
                </div>
            </div>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">전략 카드 보기 →</button>
            </div>
        </div>

        <!-- Scene 5: 전략 카드 소개 -->
        <div class="scene" id="scene5">
            <h1 class="title">전략 포트폴리오</h1>
            
            <p class="subtitle">
                매주 새로운 사고 전략을 배우고<br>
                네 전략 포트폴리오를 확장해나갈 거야.<br><br>
                이미 준비된 전략들을 살펴볼까?
            </p>

            <div class="strategy-container">
                <div class="strategy-card" onclick="selectStrategy('거꾸로 추론')">
                    <div class="strategy-level">Basic</div>
                    <div class="strategy-title">거꾸로 추론</div>
                    <div class="strategy-desc">결과에서 시작으로</div>
                    <div style="margin-top: 15px; font-size: 2em;">💡</div>
                </div>
                <div class="strategy-card" onclick="selectStrategy('조건 분해')">
                    <div class="strategy-level">Basic</div>
                    <div class="strategy-title">조건 분해</div>
                    <div class="strategy-desc">복잡함을 단순하게</div>
                    <div style="margin-top: 15px; font-size: 2em;">🔧</div>
                </div>
                <div class="strategy-card" onclick="selectStrategy('패턴 인식')">
                    <div class="strategy-level">Advanced</div>
                    <div class="strategy-title">패턴 인식</div>
                    <div class="strategy-desc">규칙성 발견하기</div>
                    <div style="margin-top: 15px; font-size: 2em;">🔍</div>
                </div>
                <div class="strategy-card" onclick="selectStrategy('전이 실험')">
                    <div class="strategy-level">Master</div>
                    <div class="strategy-title">전이 실험</div>
                    <div class="strategy-desc">다른 문제에 적용</div>
                    <div style="margin-top: 15px; font-size: 2em;">🎯</div>
                </div>
            </div>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">메타인지 체크리스트 →</button>
            </div>
        </div>

        <!-- Scene 6: 메타인지 체크리스트 -->
        <div class="scene" id="scene6">
            <h1 class="title">주간 성찰 시간</h1>
            
            <p class="subtitle">
                그리고 매주 금요일엔<br>
                네 사고 과정을 점검하는 시간을 가질 거야.<br><br>
                메타인지 체크리스트로 스스로를 돌아보는 거지.
            </p>

            <div class="checklist-container">
                <div class="checklist-title">
                    📚 메타인지 체크리스트
                </div>
                
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <div class="checkbox"></div>
                    <span>오늘 배운 전략을 설명할 수 있나요?</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <div class="checkbox"></div>
                    <span>실패한 문제의 원인을 알고 있나요?</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <div class="checkbox"></div>
                    <span>다른 문제에 적용할 수 있나요?</span>
                </div>
                <div class="checklist-item" onclick="toggleCheck(this)">
                    <div class="checkbox"></div>
                    <span>더 나은 방법을 생각해봤나요?</span>
                </div>
            </div>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">성장 경로 보기 →</button>
            </div>
        </div>

        <!-- Scene 7: 성장 경로 -->
        <div class="scene" id="scene7">
            <h1 class="title">너의 성장 곡선</h1>
            
            <p class="subtitle">
                전이 성공률 80%, 설명 점수 4/5...<br><br>
                이 숫자들이 네 사고력의 성장을 보여줄 거야.<br>
                함께 J-커브를 그려보자!
            </p>

            <div class="story-card">
                <div style="text-align: center;">
                    <h3 style="color: #667eea; margin-bottom: 20px;">📊 성장 지표</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <div style="font-size: 2em; font-weight: bold; color: #667eea;">80%</div>
                            <div style="color: #718096;">전이 성공률</div>
                        </div>
                        <div>
                            <div style="font-size: 2em; font-weight: bold; color: #667eea;">4/5</div>
                            <div style="color: #718096;">설명 점수</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">마무리 →</button>
            </div>
        </div>

        <!-- Scene 8: 마무리 -->
        <div class="scene" id="scene8">
            <div class="icon-box">🎉</div>
            <h1 class="title" id="finalGreeting">준비됐어?</h1>
            
            <p class="subtitle">
                오늘부터 시작되는 사고력 여정.<br>
                네가 생각하는 방법이 완전히 바뀔 거야.<br><br>
                <span style="font-size: 1.5em; color: #667eea; font-weight: 700;">🧠 Let's Think Different!</span>
            </p>

            <div class="story-card">
                <p style="text-align: center; font-size: 1.1em; line-height: 1.8;">
                    <strong>네가 얻게 될 것:</strong><br><br>
                    ✨ 문제를 보는 새로운 시각<br>
                    ✨ 스스로 사고하는 독립성<br>
                    ✨ 다양한 해법을 창조하는 능력<br>
                    ✨ 어떤 문제든 해결할 수 있는 자신감
                </p>
            </div>

            <div class="btn-container">
                <button class="btn btn-primary" onclick="startLearning()" style="font-size: 1.2em; padding: 20px 50px;">
                    🧠 사고력 여정 시작하기
                </button>
            </div>
        </div>
    </div>

    <script>
        // 전역 변수
        let currentScene = 0;
        const totalScenes = 9;
        let userName = '';
        let userThought = '';
        let selectedStrategies = [];

        // 초기화
        window.addEventListener('load', () => {
            createParticles();
            updateProgress();
        });

        // 파티클 생성
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // 씬 전환
        function showScene(sceneNumber) {
            document.querySelectorAll('.scene').forEach(scene => {
                scene.classList.remove('active');
            });
            
            document.getElementById(`scene${sceneNumber}`).classList.add('active');
            updateProgress();
        }

        // 진행 바 업데이트
        function updateProgress() {
            const progressBar = document.getElementById('progressBar');
            const progress = ((currentScene + 1) / totalScenes) * 100;
            progressBar.style.width = progress + '%';
        }

        // 다음 씬
        function nextScene() {
            // 현재 씬에서 데이터 수집
            if (currentScene === 1) {
                const nameInput = document.getElementById('userName');
                userName = nameInput.value.trim() || '학습자';
                
                // Scene 2에서 이름 인사 업데이트
                const greeting = document.getElementById('nameGreeting');
                greeting.textContent = `${userName}! 좋은 이름이야.`;
            }
            
            if (currentScene === 2) {
                const thoughtInput = document.getElementById('userThought');
                userThought = thoughtInput.value.trim();
                
                // Scene 3에서 사고 내용 표시
                if (userThought) {
                    const thoughtDisplay = document.getElementById('thoughtDisplay');
                    const thoughtContent = document.getElementById('thoughtContent');
                    thoughtContent.textContent = userThought;
                    thoughtDisplay.style.display = 'block';
                }
            }

            if (currentScene === 7) {
                // Scene 8에서 최종 인사 업데이트
                const finalGreeting = document.getElementById('finalGreeting');
                finalGreeting.textContent = `${userName}, 준비됐어?`;
            }

            if (currentScene < totalScenes - 1) {
                currentScene++;
                showScene(currentScene);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // 이전 씬
        function previousScene() {
            if (currentScene > 0) {
                currentScene--;
                showScene(currentScene);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // 전략 선택
        function selectStrategy(strategyName) {
            if (!selectedStrategies.includes(strategyName)) {
                selectedStrategies.push(strategyName);
                
                // 선택된 카드에 시각적 피드백
                event.target.style.background = 'linear-gradient(135deg, rgba(102, 126, 234, 0.3), rgba(118, 75, 162, 0.3))';
                event.target.style.transform = 'scale(1.05)';
                
                console.log('선택된 전략:', strategyName);
            }
        }

        // 체크박스 토글
        function toggleCheck(item) {
            const checkbox = item.querySelector('.checkbox');
            checkbox.classList.toggle('checked');
        }

        // 학습 시작
        function startLearning() {
            // 축하 효과
            confetti();
            
            setTimeout(() => {
                alert(`🎉 축하합니다, ${userName}님!\n\n사고력 중심 학습이 시작됩니다.\n첫 번째 사고 훈련 세션을 준비하고 있습니다...`);
                
                // 실제 학습 시스템으로 이동 (필요시 구현)
                // window.location.href = 'learning_system.php';
            }, 1000);
        }

        // 축하 효과
        function confetti() {
            const container = document.querySelector('.container');
            const colors = ['#667eea', '#764ba2', '#ffeaa7', '#fdcb6e', '#a8e6cf'];
            
            for (let i = 0; i < 30; i++) {
                const confettiPiece = document.createElement('div');
                confettiPiece.style.position = 'absolute';
                confettiPiece.style.width = '10px';
                confettiPiece.style.height = '10px';
                confettiPiece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confettiPiece.style.left = Math.random() * 100 + '%';
                confettiPiece.style.top = '-10px';
                confettiPiece.style.opacity = '0.8';
                confettiPiece.style.transform = `rotate(${Math.random() * 360}deg)`;
                confettiPiece.style.transition = 'all 2s ease-out';
                confettiPiece.style.borderRadius = '50%';
                confettiPiece.style.zIndex = '1000';
                container.appendChild(confettiPiece);
                
                setTimeout(() => {
                    confettiPiece.style.top = '100%';
                    confettiPiece.style.opacity = '0';
                    confettiPiece.style.transform = `rotate(${Math.random() * 720}deg)`;
                }, 10);
                
                setTimeout(() => {
                    confettiPiece.remove();
                }, 2000);
            }
        }

        // 키보드 네비게이션
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') {
                nextScene();
            } else if (e.key === 'ArrowLeft') {
                previousScene();
            }
        });
    </script>
</body>
</html>