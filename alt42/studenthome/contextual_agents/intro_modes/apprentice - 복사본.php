<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 인지적 도제형 학습 - 사고의 여정</title>
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

        /* 타이핑 효과 */
        .typewriter {
            overflow: hidden;
            white-space: nowrap;
            margin: 0 auto;
            animation: typing 2s steps(40, end);
        }

        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
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

        /* 진행 표시기 */
        .progress-container {
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 100;
        }

        .progress-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .progress-dot.active {
            background: white;
            transform: scale(1.3);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        .progress-dot.completed {
            background: rgba(255, 255, 255, 0.7);
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

        /* 메소드 카드 */
        .method-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .method-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .method-card.revealed {
            opacity: 1;
            transform: translateY(0);
        }

        .method-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .method-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .method-title {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .method-desc {
            font-size: 0.9em;
            color: #718096;
            line-height: 1.4;
        }

        /* 인터랙티브 요소 */
        .interactive-box {
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .interactive-box:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 40px rgba(253, 203, 110, 0.4);
        }

        .pulse {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 20px;
            border: 2px solid rgba(253, 203, 110, 0.5);
            animation: pulse 2s infinite;
            pointer-events: none;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(1.1);
                opacity: 0;
            }
        }

        /* KPI 대시보드 */
        .kpi-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .kpi-item {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .kpi-value {
            font-size: 2em;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .kpi-label {
            color: #718096;
            font-size: 0.9em;
            margin-top: 5px;
        }

        /* 로딩 애니메이션 */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 체크리스트 */
        .checklist {
            background: #f7fafc;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
            opacity: 0;
            transform: translateX(-20px);
            animation: slideIn 0.5s ease-out forwards;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .check-circle {
            width: 24px;
            height: 24px;
            border: 2px solid #667eea;
            border-radius: 50%;
            margin-right: 15px;
            position: relative;
            flex-shrink: 0;
        }

        .check-circle.checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #667eea;
            font-weight: bold;
        }

        /* 마지막 CTA */
        .final-cta {
            text-align: center;
            margin-top: 50px;
            padding: 40px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 20px;
        }

        .cta-title {
            font-size: 2em;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .container {
                padding: 30px;
            }

            .title {
                font-size: 1.8em;
            }

            .subtitle {
                font-size: 1.1em;
            }

            .method-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- 파티클 배경 -->
    <div class="particles" id="particles"></div>

    <!-- 진행 표시기 -->
    <div class="progress-container">
        <div class="progress-dot active" data-scene="1"></div>
        <div class="progress-dot" data-scene="2"></div>
        <div class="progress-dot" data-scene="3"></div>
        <div class="progress-dot" data-scene="4"></div>
        <div class="progress-dot" data-scene="5"></div>
        <div class="progress-dot" data-scene="6"></div>
    </div>

    <div class="container">
        <!-- Scene 1: 환영 -->
        <div class="scene active" id="scene1">
            <h1 class="title typewriter">🔍 사고의 장인이 되는 여정</h1>
            
            <p class="subtitle">
                당신은 지금 특별한 문을 열고 있습니다.<br>
                <span class="highlight">지식을 주입받는 것이 아닌, 사고하는 방법 자체를 배우는</span> 여정의 시작점에 서 있습니다.
            </p>

            <div class="story-card">
                <div class="icon-box">🧠</div>
                <div class="content">
                    <p>옛날, 위대한 장인들은 제자에게 단순히 기술을 가르치지 않았습니다.</p>
                    <p style="margin-top: 15px;">그들은 <span class="highlight">자신의 사고 과정을 보여주고</span>, 
                    제자가 스스로 그 과정을 체득하도록 이끌었습니다.</p>
                    <p style="margin-top: 15px;">이제 당신도 그 전통적인 도제 방식으로, 
                    <strong>진정한 사고의 달인</strong>이 될 준비가 되셨나요?</p>
                </div>
            </div>

            <div class="btn-container">
                <button class="btn btn-primary" onclick="nextScene()">
                    내 사고의 멘토를 만나러 가기 →
                </button>
            </div>
        </div>

        <!-- Scene 2: 핵심 철학 -->
        <div class="scene" id="scene2">
            <h1 class="title">당신만의 사고 멘토</h1>
            
            <div class="content">
                <p>🎯 <strong>우리의 핵심 신념:</strong></p>
                <div class="story-card">
                    <p style="font-size: 1.3em; text-align: center; color: #667eea; font-weight: 700;">
                        "지식은 주입이 아니라 '사고 방법'을 전수하는 것"
                    </p>
                </div>
            </div>

            <div class="interactive-box" onclick="revealMethods()">
                <div class="pulse"></div>
                <p style="text-align: center; font-weight: 600; color: #2d3748;">
                    📚 클릭하여 5단계 마스터 플랜 확인하기
                </p>
            </div>

            <div class="method-grid" id="methodGrid" style="display: none;">
                <div class="method-card">
                    <div class="method-icon">👁️</div>
                    <div class="method-title">1. 모델링</div>
                    <div class="method-desc">멘토의 사고 과정을 직접 관찰</div>
                </div>
                <div class="method-card">
                    <div class="method-icon">💬</div>
                    <div class="method-title">2. 코칭</div>
                    <div class="method-desc">당신의 사고를 언어로 표현</div>
                </div>
                <div class="method-card">
                    <div class="method-icon">🪜</div>
                    <div class="method-title">3. 스캐폴딩</div>
                    <div class="method-desc">점진적으로 독립성 획득</div>
                </div>
                <div class="method-card">
                    <div class="method-icon">💎</div>
                    <div class="method-title">4. 명료화</div>
                    <div class="method-desc">전략을 체계적으로 정리</div>
                </div>
                <div class="method-card">
                    <div class="method-icon">🚀</div>
                    <div class="method-title">5. 탐색</div>
                    <div class="method-desc">다양한 해법을 창조</div>
                </div>
            </div>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">나의 성장 목표 확인 →</button>
            </div>
        </div>

        <!-- Scene 3: 목표와 KPI -->
        <div class="scene" id="scene3">
            <h1 class="title">당신의 성장 지표</h1>
            
            <p class="subtitle">
                인지적 도제 학습에서 당신이 달성하게 될 목표들입니다.
            </p>

            <div class="kpi-dashboard">
                <div class="kpi-item">
                    <div class="kpi-value">80%</div>
                    <div class="kpi-label">전이 성공률</div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-value">60%</div>
                    <div class="kpi-label">다양한 풀이</div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-value">4/5</div>
                    <div class="kpi-label">발화 품질</div>
                </div>
                <div class="kpi-item">
                    <div class="kpi-value">0</div>
                    <div class="kpi-label">최종 스캐폴딩</div>
                </div>
            </div>

            <div class="story-card">
                <p><strong>🎯 당신의 여정:</strong></p>
                <ul style="list-style: none; padding: 20px 0;">
                    <li style="margin: 10px 0;">✨ 배운 개념을 새로운 문제에 자유롭게 적용</li>
                    <li style="margin: 10px 0;">✨ 하나의 문제에 여러 해법을 발견</li>
                    <li style="margin: 10px 0;">✨ 사고 과정을 명확하게 설명</li>
                    <li style="margin: 10px 0;">✨ 완전한 독립적 문제 해결 능력</li>
                </ul>
            </div>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">주간 학습 루틴 보기 →</button>
            </div>
        </div>

        <!-- Scene 4: 주간 루틴 -->
        <div class="scene" id="scene4">
            <h1 class="title">당신의 주간 여정</h1>
            
            <div class="content">
                <p class="subtitle">체계적이면서도 유연한 학습 리듬</p>
            </div>

            <div class="checklist">
                <h3 style="color: #667eea; margin-bottom: 20px;">📅 Weekly Journey Map</h3>
                
                <div class="checklist-item" style="animation-delay: 0.1s;">
                    <div class="check-circle"></div>
                    <div>
                        <strong>월·수요일:</strong> 모델링 & 코칭 세션<br>
                        <span style="color: #718096; font-size: 0.9em;">멘토의 사고를 관찰하고 당신의 사고를 표현</span>
                    </div>
                </div>
                
                <div class="checklist-item" style="animation-delay: 0.2s;">
                    <div class="check-circle"></div>
                    <div>
                        <strong>금요일:</strong> 스캐폴딩 & 명료화<br>
                        <span style="color: #718096; font-size: 0.9em;">독립성을 키우고 전략을 정리</span>
                    </div>
                </div>
                
                <div class="checklist-item" style="animation-delay: 0.3s;">
                    <div class="check-circle"></div>
                    <div>
                        <strong>주말:</strong> 탐색 과제<br>
                        <span style="color: #718096; font-size: 0.9em;">창의적인 해법 발견의 시간</span>
                    </div>
                </div>
            </div>

            <div class="story-card">
                <p style="text-align: center; font-style: italic; color: #4a5568;">
                    "매주 당신은 조금씩 더 독립적인 사고자가 되어갑니다.<br>
                    처음엔 멘토의 손을 잡고, 점차 혼자서도 걸을 수 있게 됩니다."
                </p>
            </div>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">맞춤형 지원 시스템 →</button>
            </div>
        </div>

        <!-- Scene 5: 지능형 지원 -->
        <div class="scene" id="scene5">
            <h1 class="title">당신을 위한 지능형 지원</h1>
            
            <div class="content">
                <p class="subtitle">상황에 따라 자동으로 조정되는 맞춤형 학습</p>
            </div>

            <div class="method-grid">
                <div class="method-card revealed">
                    <div class="method-icon">🎯</div>
                    <div class="method-title">맞춤 전환</div>
                    <div class="method-desc">
                        설명이 어려울 때 → 집중 모드<br>
                        시험 2주 전 → 실전 대비 모드
                    </div>
                </div>
                <div class="method-card revealed">
                    <div class="method-icon">📊</div>
                    <div class="method-title">실시간 분석</div>
                    <div class="method-desc">
                        모든 학습 데이터를 분석하여<br>
                        최적의 다음 단계 제시
                    </div>
                </div>
                <div class="method-card revealed">
                    <div class="method-icon">🤖</div>
                    <div class="method-title">AI 코치</div>
                    <div class="method-desc">
                        24/7 당신의 사고 과정을<br>
                        관찰하고 피드백 제공
                    </div>
                </div>
            </div>

            <div class="interactive-box" style="background: linear-gradient(135deg, #a8e6cf, #7fcdbb);">
                <p style="text-align: center; font-weight: 600; color: #2d3748;">
                    🎮 당신의 학습 스타일에 따라<br>
                    7가지 보조 모드가 자동으로 결합됩니다
                </p>
            </div>

            <div class="btn-container">
                <button class="btn btn-secondary" onclick="previousScene()">← 이전</button>
                <button class="btn btn-primary" onclick="nextScene()">시작할 준비 완료! →</button>
            </div>
        </div>

        <!-- Scene 6: 최종 CTA -->
        <div class="scene" id="scene6">
            <div class="final-cta">
                <h1 class="cta-title">🎊 당신의 여정이 시작됩니다</h1>
                
                <div class="content" style="text-align: center;">
                    <p style="font-size: 1.2em; margin-bottom: 30px;">
                        <span class="highlight">사고의 장인</span>이 되는 여정,<br>
                        이제 첫 발을 내딛을 시간입니다.
                    </p>
                </div>

                <div class="story-card" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));">
                    <p style="font-size: 1.1em; text-align: center; line-height: 1.8;">
                        <strong>당신이 얻게 될 것:</strong><br><br>
                        ✨ 문제를 보는 새로운 시각<br>
                        ✨ 스스로 사고하는 독립성<br>
                        ✨ 다양한 해법을 창조하는 능력<br>
                        ✨ 어떤 문제든 해결할 수 있는 자신감
                    </p>
                </div>

                <div class="btn-container">
                    <button class="btn btn-primary" onclick="startJourney()" style="font-size: 1.2em; padding: 20px 50px;">
                        🔍 인지적 도제 학습 시작하기
                    </button>
                </div>

                <p style="text-align: center; margin-top: 30px; color: #718096; font-size: 0.9em;">
                    "가장 위대한 스승은 답을 주는 사람이 아니라,<br>
                    스스로 답을 찾는 방법을 가르치는 사람이다."
                </p>
            </div>
        </div>
    </div>

    <script>
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

        // 현재 씬 추적
        let currentScene = 1;
        const totalScenes = 6;

        // 씬 전환 함수
        function showScene(sceneNumber) {
            // 모든 씬 숨기기
            document.querySelectorAll('.scene').forEach(scene => {
                scene.classList.remove('active');
            });
            
            // 선택된 씬 표시
            document.getElementById(`scene${sceneNumber}`).classList.add('active');
            
            // 진행 표시기 업데이트
            updateProgress(sceneNumber);
            
            // 씬별 특수 효과
            if (sceneNumber === 2) {
                setTimeout(() => {
                    const methodGrid = document.getElementById('methodGrid');
                    if (methodGrid.style.display === 'none') {
                        // 자동으로 메소드 표시 (선택적)
                    }
                }, 1000);
            }
        }

        // 진행 표시기 업데이트
        function updateProgress(sceneNumber) {
            document.querySelectorAll('.progress-dot').forEach((dot, index) => {
                dot.classList.remove('active');
                if (index < sceneNumber - 1) {
                    dot.classList.add('completed');
                } else if (index === sceneNumber - 1) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('completed');
                }
            });
        }

        // 다음 씬으로
        function nextScene() {
            if (currentScene < totalScenes) {
                currentScene++;
                showScene(currentScene);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // 이전 씬으로
        function previousScene() {
            if (currentScene > 1) {
                currentScene--;
                showScene(currentScene);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // 메소드 카드 표시
        function revealMethods() {
            const methodGrid = document.getElementById('methodGrid');
            methodGrid.style.display = 'grid';
            
            // 순차적으로 카드 애니메이션
            const cards = methodGrid.querySelectorAll('.method-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('revealed');
                }, index * 100);
            });
        }

        // 체크리스트 애니메이션
        function animateChecklist() {
            const checkCircles = document.querySelectorAll('.check-circle');
            checkCircles.forEach((circle, index) => {
                setTimeout(() => {
                    circle.classList.add('checked');
                }, 1000 + (index * 300));
            });
        }

        // 여정 시작
        function startJourney() {
            // 축하 애니메이션
            confetti();
            
            // 실제 학습 시작 로직
            setTimeout(() => {
                alert('🎉 축하합니다! 인지적 도제 학습이 시작됩니다.\n\n첫 번째 모델링 세션을 준비하고 있습니다...');
                // 여기에 실제 학습 페이지로 이동하는 로직 추가
            }, 1000);
        }

        // 간단한 축하 효과
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

        // 진행 표시기 클릭 이벤트
        document.querySelectorAll('.progress-dot').forEach(dot => {
            dot.addEventListener('click', function() {
                const targetScene = parseInt(this.dataset.scene);
                if (targetScene <= currentScene || targetScene === currentScene + 1) {
                    currentScene = targetScene;
                    showScene(currentScene);
                }
            });
        });

        // Scene 4에서 체크리스트 애니메이션 자동 실행
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.target.id === 'scene4') {
                    setTimeout(animateChecklist, 500);
                }
            });
        });

        document.querySelectorAll('.scene').forEach(scene => {
            observer.observe(scene);
        });

        // 초기화
        window.addEventListener('load', () => {
            createParticles();
            showScene(1);
        });

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